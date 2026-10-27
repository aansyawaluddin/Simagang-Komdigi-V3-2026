<?php

namespace App\Http\Controllers\Workspace;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\TaskChecklistItem;
use App\Models\TaskTemplate;
use App\Models\Workspace;
use App\Services\TaskNotifier;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    /** Batas tugas aktif yang dimuat di board per workspace. */
    private const BOARD_ACTIVE_LIMIT = 200;
    private const BOARD_DONE_LIMIT = 50;

    private const RELATIONS = ['assignees', 'labels', 'checklistItems', 'creator'];

    public function __construct(private TaskService $tasks, private TaskNotifier $notifier)
    {
    }

    /** Board / list tugas (JSON). Filter: assignee, status, priority, label, due_from, due_to. */
    public function index(Request $request, Workspace $workspace): AnonymousResourceCollection
    {
        $this->authorize('view', $workspace);

        $filters = $request->validate([
            'assignee' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
            'priority' => ['nullable', Rule::enum(TaskPriority::class)],
            'label' => ['nullable', 'string', 'max:50'],
            'due_from' => ['nullable', 'date'],
            'due_to' => ['nullable', 'date'],
        ]);

        $query = fn () => Task::query()
            ->where('workspace_id', $workspace->id)
            ->with(self::RELATIONS)
            ->when($filters['assignee'] ?? null, fn ($q, $id) => $q->whereHas('assignees', fn ($a) => $a->where('interns.id', $id)))
            ->when($filters['priority'] ?? null, fn ($q, $p) => $q->where('priority', $p))
            ->when($filters['label'] ?? null, fn ($q, $name) => $q->whereHas('labels', fn ($l) => $l->where('name', $name)))
            ->when($filters['due_from'] ?? null, fn ($q, $d) => $q->where('due_at', '>=', $d))
            ->when($filters['due_to'] ?? null, fn ($q, $d) => $q->where('due_at', '<=', $d))
            ->orderBy('position')
            ->orderBy('id');

        if (! empty($filters['status'])) {
            $tasks = $query()->where('status', $filters['status'])->limit(self::BOARD_ACTIVE_LIMIT)->get();
        } else {
            $active = $query()->where('status', '!=', TaskStatus::Done->value)->limit(self::BOARD_ACTIVE_LIMIT)->get();
            $done = $query()->where('status', TaskStatus::Done->value)->reorder('completed_at', 'desc')->limit(self::BOARD_DONE_LIMIT)->get();
            $tasks = $active->concat($done);
        }

        return TaskResource::collection($tasks);
    }

    /**
     * Buat tugas. Dengan template_id dan banyak assignee, tiap intern mendapat salinan tugasnya sendiri.
     * Intern hanya bisa membuat to-do pribadi di Space-nya.
     */
    public function store(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('create', [Task::class, $workspace]);

        $rules = [
            'title' => ['required_without:template_id', 'nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:10000'],
            'priority' => ['nullable', Rule::enum(TaskPriority::class)],
            'start_at' => ['nullable', 'date'],
            'due_at' => array_filter(['required', 'date', $request->filled('start_at') ? 'after_or_equal:start_at' : null]),
            'assignee_ids' => ['nullable', 'array'],
            'assignee_ids.*' => ['integer', 'distinct'],
            'labels' => ['nullable', 'array', 'max:20'],
            'labels.*' => ['string', 'max:50'],
            'checklist' => ['nullable', 'array', 'max:50'],
            'checklist.*' => ['string', 'max:255'],
            'template_id' => ['nullable', 'integer', 'exists:task_templates,id'],
        ];
        $data = $request->validate($rules);

        $user = $request->user();
        $template = null;

        if (! empty($data['template_id'])) {
            $template = TaskTemplate::findOrFail($data['template_id']);
            $this->authorize('view', $template);

            $data['title'] = $data['title'] ?? $template->title;
            $data['description'] = $data['description'] ?? $template->description;
            $data['checklist'] = $data['checklist'] ?? $template->checklist ?? [];
        }

        $internIds = $this->resolveAssignees($request, $workspace, $data['assignee_ids'] ?? []);

        $created = DB::transaction(function () use ($workspace, $user, $data, $internIds, $template) {
            // Template + banyak intern = satu salinan tugas per intern.
            $groups = $template ? array_map(fn ($id) => [$id], $internIds) : [$internIds];

            return collect($groups)->map(
                fn (array $ids) => $this->tasks->create($workspace, $user, $data, $ids, $template ? ['source' => 'template'] : [])
            );
        });

        $created->each(function (Task $task) use ($user) {
            $task->load(self::RELATIONS);
            $this->notifier->assigned($task, $task->assignees->pluck('id')->all(), $user);
        });

        return TaskResource::collection($created)->response()->setStatusCode(201);
    }

    public function show(Task $task): TaskResource
    {
        $this->authorize('view', $task);

        $task->load([...self::RELATIONS, 'attachments', 'comments.user', 'activities.user']);

        return new TaskResource($task);
    }

    public function update(Request $request, Task $task): TaskResource
    {
        $this->authorize('update', $task);

        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
            'start_at' => ['sometimes', 'nullable', 'date'],
            'due_at' => ['sometimes', 'required', 'date'],
            'result_link' => ['sometimes', 'nullable', 'url', 'max:500'],
            'assignee_ids' => ['sometimes', 'array', 'min:1'],
            'assignee_ids.*' => ['integer', 'distinct'],
            'labels' => ['sometimes', 'array', 'max:20'],
            'labels.*' => ['string', 'max:50'],
            'checklist' => ['sometimes', 'array', 'max:50'],
            'checklist.*' => ['string', 'max:255'],
        ]);

        $start = array_key_exists('start_at', $data) ? $data['start_at'] : $task->start_at;
        $due = $data['due_at'] ?? $task->due_at;
        if ($start && $due && \Carbon\Carbon::parse($due)->lt(\Carbon\Carbon::parse($start))) {
            throw ValidationException::withMessages(['due_at' => 'Deadline tidak boleh sebelum tanggal mulai.']);
        }

        // Intern hanya mengubah to-do pribadinya; assignee tetap dirinya sendiri.
        if ($request->user()->isIntern()) {
            unset($data['assignee_ids']);
        }

        $addedInternIds = [];

        DB::transaction(function () use ($task, $data, $request, &$addedInternIds) {
            $task->fill(collect($data)->only(['title', 'description', 'priority', 'start_at', 'due_at', 'result_link'])->all());

            // Deadline diperpanjang: penanda terlambat dan pengingat boleh berlaku lagi.
            if ($task->isDirty('due_at')) {
                $task->overdue_at = null;
                $task->reminded_at = null;
            }

            $changed = array_keys($task->getDirty());
            $task->save();

            if (isset($data['assignee_ids'])) {
                $ids = $this->resolveAssignees($request, $task->workspace, $data['assignee_ids']);
                $diff = $task->assignees()->sync($ids);

                $addedInternIds = $diff['attached'];

                if ($diff['attached'] || $diff['detached']) {
                    $this->tasks->record($task, $request->user(), 'assignees_changed', [
                        'added' => $diff['attached'],
                        'removed' => $diff['detached'],
                    ]);
                }
            }

            if (array_key_exists('labels', $data)) {
                $this->tasks->syncLabels($task, $data['labels']);
            }

            if (array_key_exists('checklist', $data)) {
                $this->tasks->replaceChecklist($task, $data['checklist']);
                $changed[] = 'checklist';
            }

            if ($changed) {
                $this->tasks->record($task, $request->user(), 'updated', ['fields' => array_values(array_diff($changed, ['updated_at', 'overdue_at', 'reminded_at']))]);
            }
        });

        if ($addedInternIds) {
            $this->notifier->assigned($task, $addedInternIds, $request->user());
        }

        return new TaskResource($task->fresh([...self::RELATIONS]));
    }

    /** Drag and drop: pindah status dan posisi kartu. Intern maksimal sampai Review. */
    public function updateStatus(Request $request, Task $task): TaskResource
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'position' => ['nullable', 'integer', 'min:0'],
            'result_link' => ['nullable', 'url', 'max:500'],
        ]);

        $to = TaskStatus::from($data['status']);

        $this->authorize('updateStatus', [$task, $to]);

        $from = $task->status;

        DB::transaction(function () use ($request, $task, $to, $data) {
            if (! empty($data['result_link'])) {
                $task->result_link = $data['result_link'];
            }

            $this->tasks->move($task, $to, $data['position'] ?? null, $request->user());
        });

        if ($to === TaskStatus::Review && $from !== TaskStatus::Review) {
            $this->notifier->reviewRequested($task, $request->user());
        }

        return new TaskResource($task->fresh(self::RELATIONS));
    }

    /** Mentor/admin memutuskan Selesai atau Revisi. Revisi wajib disertai catatan. */
    public function review(Request $request, Task $task): TaskResource
    {
        $this->authorize('review', $task);

        $data = $request->validate([
            'decision' => ['required', Rule::in([TaskStatus::Done->value, TaskStatus::Revision->value])],
            'feedback' => ['required_if:decision,' . TaskStatus::Revision->value, 'nullable', 'string', 'max:5000'],
            'score' => ['nullable', 'integer', 'between:0,100'],
        ]);

        if ($task->status !== TaskStatus::Review) {
            throw ValidationException::withMessages(['decision' => 'Hanya tugas berstatus Review yang bisa dinilai.']);
        }

        $decision = TaskStatus::from($data['decision']);

        DB::transaction(function () use ($request, $task, $decision, $data) {
            $isDone = $decision === TaskStatus::Done;

            $task->feedback = $data['feedback'] ?? null;
            $task->score = $isDone ? ($data['score'] ?? null) : null;
            $task->completed_at = $isDone ? now() : null;

            $this->tasks->move($task, $decision, null, $request->user(), [
                'score' => $task->score,
                'feedback' => $task->feedback,
            ]);
        });

        $this->notifier->reviewed($task, $request->user());

        return new TaskResource($task->fresh(self::RELATIONS));
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $this->tasks->record($task, $request->user(), 'deleted');
        $task->delete();

        return response()->json(null, 204);
    }

    /** Centang / batal centang sub-item checklist. */
    public function toggleChecklistItem(Request $request, Task $task, TaskChecklistItem $item): JsonResponse
    {
        abort_unless($item->task_id === $task->id, 404);

        $this->authorize('updateChecklist', $task);

        $data = $request->validate(['is_done' => ['required', 'boolean']]);

        $item->update(['is_done' => $data['is_done']]);

        $items = $task->checklistItems()->get();

        return response()->json([
            'id' => $item->id,
            'is_done' => $item->is_done,
            'checklist_progress' => $items->isEmpty() ? null : (int) round($items->where('is_done', true)->count() / $items->count() * 100),
        ]);
    }

    /**
     * Intern selalu menugaskan dirinya sendiri (to-do pribadi). Admin/mentor wajib memilih
     * minimal satu intern yang memang anggota workspace.
     *
     * @param  array<int, int>  $requested
     * @return array<int, int>
     */
    private function resolveAssignees(Request $request, Workspace $workspace, array $requested): array
    {
        $user = $request->user();

        if ($user->isIntern()) {
            return [$user->intern->id];
        }

        if (! $requested) {
            throw ValidationException::withMessages(['assignee_ids' => 'Pilih minimal satu intern.']);
        }

        $allowed = $this->tasks->allowedAssigneeIds($workspace, $requested);

        if (count($allowed) !== count($requested)) {
            throw ValidationException::withMessages(['assignee_ids' => 'Ada intern yang bukan anggota workspace ini.']);
        }

        return $allowed;
    }
}
