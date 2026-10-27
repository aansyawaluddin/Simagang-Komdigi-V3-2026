<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TaskTemplate::class);

        $user = $request->user();

        $templates = TaskTemplate::query()
            ->when(! $user->isSuperAdmin() && ! $user->isAdminFull(), function ($q) use ($user) {
                $teamId = optional($user->mentor)->team_id;
                $q->where(fn ($w) => $w->whereNull('team_id')->when($teamId, fn ($x) => $x->orWhere('team_id', $teamId)));
            })
            ->orderBy('title')
            ->get();

        return response()->json(['data' => $templates]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', TaskTemplate::class);

        $data = $this->validated($request);

        $template = TaskTemplate::create($data + ['created_by' => $request->user()->id]);

        return response()->json(['data' => $template], 201);
    }

    /** Simpan tugas yang sudah ada sebagai template (judul, deskripsi, checklist). */
    public function storeFromTask(Request $request, Task $task): JsonResponse
    {
        $this->authorize('view', $task);
        $this->authorize('create', TaskTemplate::class);

        $user = $request->user();

        $template = TaskTemplate::create([
            'team_id' => $this->resolveTeamId($request, $request->input('team_id')),
            'created_by' => $user->id,
            'title' => $task->title,
            'description' => $task->description,
            'checklist' => $task->checklistItems->pluck('content')->all(),
            'is_onboarding' => false,
        ]);

        return response()->json(['data' => $template], 201);
    }

    public function update(Request $request, TaskTemplate $template): JsonResponse
    {
        $this->authorize('update', $template);

        $template->update($this->validated($request, $template));

        return response()->json(['data' => $template->fresh()]);
    }

    public function destroy(TaskTemplate $template): JsonResponse
    {
        $this->authorize('delete', $template);

        $template->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request, ?TaskTemplate $template = null): array
    {
        $data = $request->validate([
            'title' => [$template ? 'sometimes' : 'required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:10000'],
            'checklist' => ['nullable', 'array', 'max:50'],
            'checklist.*' => ['string', 'max:255'],
            'is_onboarding' => ['sometimes', 'boolean'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
        ]);

        // Mentor selalu terikat ke timnya; hanya admin yang boleh membuat template global (team_id kosong).
        if ($template === null || array_key_exists('team_id', $data) || ! $this->isAdmin($request)) {
            $data['team_id'] = $this->resolveTeamId($request, $data['team_id'] ?? null);
        }

        return $data;
    }

    private function resolveTeamId(Request $request, mixed $requested): ?int
    {
        return $this->isAdmin($request)
            ? ($requested ? (int) $requested : null)
            : optional($request->user()->mentor)->team_id;
    }

    private function isAdmin(Request $request): bool
    {
        $user = $request->user();

        return $user->isSuperAdmin() || $user->isAdminFull();
    }
}
