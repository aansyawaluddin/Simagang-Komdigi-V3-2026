<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Channel;
use App\Models\Intern;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Mengubah model Workspace menjadi bentuk data yang diminta frontend
 * (docs/workspace/kontrak-frontend.md). Model data tetap Workspace tim + Space Intern;
 * keduanya ditampilkan frontend sebagai "kelompok".
 */
class WorkspacePresenter
{
    /** Warna ikon kelompok (kontras teks putih >= 4,5:1), dipilih deterministik dari id. */
    private const COLORS = ['#4f46e5', '#0f766e', '#be185d', '#b45309', '#1d4ed8', '#7e22ce', '#047857', '#b91c1c'];

    public function __construct(private ChannelUnreadService $unread)
    {
    }

    public function markdown(?string $text): string
    {
        return $text === null || $text === ''
            ? ''
            : trim(Str::markdown($text, ['html_input' => 'escape', 'allow_unsafe_links' => false]));
    }

    public function iso($date): ?string
    {
        return $date?->toIso8601String();
    }

    private function avatar(?string $path): ?string
    {
        return $path ? Storage::url($path) : null;
    }

    public function user(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        $role = match (true) {
            $user->isSuperAdmin() || $user->isAdminFull() => 'admin',
            $user->isMentor() => 'mentor',
            $user->isIntern() => 'intern',
            default => 'admin',
        };

        $photo = match ($role) {
            'mentor' => $user->mentor?->photo_path,
            'intern' => $user->intern?->photo_path,
            default => null,
        };

        return ['id' => $user->id, 'name' => $user->name, 'role' => $role, 'avatar_url' => $this->avatar($photo)];
    }

    public function intern(Intern $intern): array
    {
        return [
            'id' => $intern->id,
            'user_id' => $intern->user_id,
            'name' => $intern->name,
            'avatar_url' => $this->avatar($intern->photo_path),
        ];
    }

    /** @return array{todo:int,in_progress:int,review:int,revision:int,done:int,late:int} */
    public function stats(Workspace $workspace): array
    {
        $stats = array_fill_keys(array_map(fn (TaskStatus $s) => $s->value, TaskStatus::cases()), 0);

        $rows = DB::table('tasks')
            ->where('workspace_id', $workspace->id)
            ->whereNull('deleted_at')
            ->selectRaw('status, count(*) as total, sum(case when overdue_at is not null and status != ? then 1 else 0 end) as late', [TaskStatus::Done->value])
            ->groupBy('status')
            ->get();

        $late = 0;
        foreach ($rows as $row) {
            $stats[$row->status] = (int) $row->total;
            $late += (int) $row->late;
        }

        return $stats + ['late' => $late];
    }

    public function workspace(Workspace $workspace, User $viewer): array
    {
        $workspace->loadMissing(['team', 'intern']);

        $members = DB::table('workspace_members')
            ->join('users', 'users.id', '=', 'workspace_members.user_id')
            ->where('workspace_members.workspace_id', $workspace->id)
            ->whereNull('workspace_members.left_at')
            ->orderByRaw("case workspace_members.role when 'mentor' then 0 when 'owner' then 1 else 2 end")
            ->orderBy('users.name')
            ->select('users.id', 'workspace_members.role')
            ->get();

        $users = User::with(['mentor', 'intern'])->whereIn('id', $members->pluck('id'))->get()->keyBy('id');
        $minis = $members->map(fn ($m) => $this->user($users[$m->id] ?? null))->filter()->values();
        $mentor = $members->firstWhere('role', 'mentor');

        $endDate = $workspace->type === 'intern'
            ? $workspace->intern?->end_date
            : Intern::where('team_id', $workspace->team_id)->where('is_active', true)->max('end_date');

        $channelIds = Channel::where('workspace_id', $workspace->id)->where('is_archived', false)->pluck('id')->all();
        $unread = array_sum($this->unread->counts($viewer, $channelIds));

        return [
            'id' => $workspace->id,
            'type' => 'group',
            'name' => $workspace->name,
            'slug' => $workspace->slug,
            'color' => self::COLORS[$workspace->id % count(self::COLORS)],
            'description' => $workspace->type === 'intern' ? 'Space pribadi intern dan mentor pembimbing.' : null,
            'team' => ['id' => $workspace->team_id, 'name' => $workspace->team?->name ?? '-'],
            'mentor' => $mentor ? $this->user($users[$mentor->id] ?? null) : ['id' => 0, 'name' => '-', 'role' => 'mentor', 'avatar_url' => null],
            'status' => $workspace->is_archived ? 'finished' : 'active',
            'created_at' => $this->iso($workspace->created_at),
            'finished_at' => $workspace->is_archived ? $this->iso($workspace->updated_at) : null,
            'end_date' => $endDate ? \Illuminate\Support\Carbon::parse($endDate)->toDateString() : null,
            'is_archived' => $workspace->is_archived,
            'is_read_only' => $workspace->is_archived,
            'stats' => $this->stats($workspace),
            'unread_messages' => (int) $unread,
            'members_count' => $minis->count(),
            'members_preview' => $minis->take(4)->all(),
            // Pengelolaan kelompok manual belum ada: workspace dibuat otomatis oleh sistem.
            'can' => ['rename' => false, 'add_members' => false, 'finish' => false],
            'url' => route('workspace.show', $workspace),
        ];
    }

    /** @return array<int, array> anggota aktif workspace sebagai UserMini */
    public function members(Workspace $workspace): array
    {
        $ids = DB::table('workspace_members')
            ->where('workspace_id', $workspace->id)
            ->whereNull('left_at')
            ->pluck('user_id');

        return User::with(['mentor', 'intern'])->whereIn('id', $ids)->orderBy('name')->get()
            ->map(fn (User $u) => $this->user($u))
            ->sortBy(fn ($u) => $u['role'] === 'mentor' ? 0 : 1)
            ->values()
            ->all();
    }

    public function taskCard(Task $task, User $viewer): array
    {
        $items = $task->relationLoaded('checklistItems') ? $task->checklistItems : collect();
        $done = $task->status === TaskStatus::Done;
        $gate = Gate::forUser($viewer);

        return [
            'id' => $task->id,
            'workspace_id' => $task->workspace_id,
            'code' => $task->code,
            'title' => $task->title,
            'status' => $task->status->value,
            'priority' => $task->priority->value,
            'position' => $task->position,
            'start_at' => $this->iso($task->start_at),
            'due_at' => $this->iso($task->due_at),
            'completed_at' => $this->iso($task->completed_at),
            'is_late' => ! $done && $task->overdue_at !== null,
            'labels' => $task->relationLoaded('labels')
                ? $task->labels->map(fn ($l) => ['id' => $l->id, 'name' => $l->name, 'color' => $l->color])->values()->all()
                : [],
            'assignees' => $task->relationLoaded('assignees') ? $task->assignees->map(fn ($i) => $this->intern($i))->values()->all() : [],
            'checklist' => ['done' => $items->where('is_done', true)->count(), 'total' => $items->count()],
            'comments_count' => (int) ($task->comments_count ?? 0),
            'attachments_count' => (int) ($task->attachments_count ?? 0),
            'url' => route('workspace.tasks.show', $task),
            'can' => [
                'update' => $gate->allows('update', $task),
                'move' => $gate->allows('updateStatus', [$task, TaskStatus::InProgress]) || $gate->allows('updateStatus', [$task, TaskStatus::Review]),
                'review' => $gate->allows('review', $task),
                'delete' => $gate->allows('delete', $task),
            ],
        ];
    }

    public function taskDetail(Task $task, User $viewer): array
    {
        $task->loadMissing(['workspace', 'creator', 'comments.user', 'attachments', 'activities.user']);
        $gate = Gate::forUser($viewer);

        $card = $this->taskCard($task, $viewer);

        return array_merge($card, [
            'description' => $task->description,
            'description_html' => $this->markdown($task->description),
            'creator' => $this->user($task->creator),
            'workspace' => ['id' => $task->workspace->id, 'name' => $task->workspace->name, 'type' => 'group', 'url' => route('workspace.show', $task->workspace)],
            'checklist_items' => $task->checklistItems->map(fn ($i) => [
                'id' => $i->id,
                'content' => $i->content,
                'is_done' => (bool) $i->is_done,
                'position' => $i->position,
            ])->values()->all(),
            'attachments' => $task->attachments->map(fn ($a) => $this->attachment($a))->values()->all(),
            'result_links' => $task->result_link ? [['url' => $task->result_link, 'label' => null]] : [],
            'score' => $task->score,
            'feedback' => $task->feedback,
            'feedback_html' => $this->markdown($task->feedback),
            'comments' => $task->comments->map(fn ($c) => [
                'id' => $c->id,
                'user' => $this->user($c->user) ?? ['id' => 0, 'name' => 'Pengguna dihapus', 'role' => 'intern', 'avatar_url' => null],
                'body' => $c->body,
                'body_html' => $this->markdown($c->body),
                'attachments' => $c->relationLoaded('attachments') ? $c->attachments->map(fn ($a) => $this->attachment($a))->values()->all() : [],
                'created_at' => $this->iso($c->created_at),
                'is_deleted' => false,
                'can' => ['delete' => false],
            ])->values()->all(),
            'activities' => $task->activities->sortByDesc('id')->map(fn ($a) => $this->activity($a, $task))->values()->all(),
            'can' => $card['can'] + ['comment' => $gate->allows('comment', $task)],
        ]);
    }

    public function attachment($a): array
    {
        $image = str_starts_with((string) $a->mime, 'image/');

        return [
            'id' => $a->id,
            'original_name' => $a->original_name,
            'mime' => $a->mime,
            'size' => (int) $a->size,
            'is_image' => $image,
            'thumbnail_url' => $image ? route('workspace.attachments.download', $a) : null,
            'download_url' => route('workspace.attachments.download', $a),
        ];
    }

    public function activity(TaskActivity $a, ?Task $task = null): array
    {
        $task ??= $a->task;

        // Nama aksi backend yang berbeda dari yang dibaca frontend.
        $action = match ($a->action) {
            'overdue' => 'marked_late',
            'assignees_changed' => 'assigned',
            default => $a->action,
        };

        return [
            'id' => $a->id,
            'user' => $this->user($a->user),
            'action' => $action,
            'meta' => $a->meta ?? new \stdClass(),
            'task' => $task ? ['id' => $task->id, 'code' => $task->code, 'title' => $task->title, 'url' => route('workspace.tasks.show', $task)] : null,
            'created_at' => $this->iso($a->created_at),
        ];
    }

    /** @return array<int, array> channel yang boleh dilihat user di workspace ini + channel global */
    public function channels(Workspace $workspace, User $viewer): array
    {
        $channels = Channel::query()
            ->where(fn ($q) => $q->where('workspace_id', $workspace->id)->orWhereNull('workspace_id'))
            ->where(fn ($q) => $viewer->isSuperAdmin() || $viewer->isAdminFull()
                ? $q
                : $q->whereHas('members', fn ($m) => $m->where('users.id', $viewer->id)))
            ->orderBy('workspace_id')
            ->orderBy('name')
            ->get()
            ->filter(fn (Channel $c) => Gate::forUser($viewer)->allows('view', $c))
            ->values();

        $unread = $this->unread->counts($viewer, $channels->pluck('id')->all());
        $lastRead = DB::table('channel_members')->where('user_id', $viewer->id)
            ->whereIn('channel_id', $channels->pluck('id'))->pluck('last_read_message_id', 'channel_id');
        $memberCounts = DB::table('channel_members')->whereIn('channel_id', $channels->pluck('id'))
            ->selectRaw('channel_id, count(*) as total')->groupBy('channel_id')->pluck('total', 'channel_id');
        $muted = DB::table('channel_members')->where('user_id', $viewer->id)
            ->whereIn('channel_id', $channels->pluck('id'))->pluck('is_muted', 'channel_id');

        return $channels->map(fn (Channel $c) => $this->channel($c, $viewer, [
            'unread' => $unread[$c->id] ?? 0,
            'last_read' => $lastRead[$c->id] ?? null,
            'members_count' => $memberCounts[$c->id] ?? 0,
            'muted' => (bool) ($muted[$c->id] ?? false),
        ]))->all();
    }

    public function channel(Channel $c, User $viewer, array $extra = []): array
    {
        $gate = Gate::forUser($viewer);
        $global = $c->workspace_id === null;

        return [
            'id' => $c->id,
            'workspace_id' => $c->workspace_id,
            'name' => $c->name,
            'type' => $c->type,
            'description' => null,
            'is_private' => $c->is_private,
            'is_archived' => $c->is_archived,
            'members_count' => $global && $c->type === 'announcement' ? null : ($extra['members_count'] ?? null),
            'members' => null,
            'unread_count' => (int) ($extra['unread'] ?? 0),
            'last_read_message_id' => $extra['last_read'] ?? null,
            'is_muted' => (bool) ($extra['muted'] ?? false),
            'can' => [
                'post' => $gate->allows('post', $c),
                'manage' => $gate->allows('archive', $c),
                'pin' => $gate->allows('moderate', $c),
            ],
        ];
    }

    public function template(TaskTemplate $t, User $viewer): array
    {
        $gate = Gate::forUser($viewer);

        return [
            'id' => $t->id,
            'team' => $t->team ? ['id' => $t->team->id, 'name' => $t->team->name] : null,
            'title' => $t->title,
            'description' => $t->description,
            'description_html' => $this->markdown($t->description),
            'priority' => 'medium',
            'labels' => [],
            'due_in_days' => 7,
            'checklist' => collect($t->checklist ?? [])->map(fn ($c) => ['content' => is_array($c) ? ($c['content'] ?? '') : (string) $c])->values()->all(),
            'is_onboarding' => (bool) $t->is_onboarding,
            'creator' => $this->user($t->creator),
            'usage_count' => (int) DB::table('tasks')->where('template_id', $t->id)->count(),
            'updated_at' => $this->iso($t->updated_at),
            'can' => ['update' => $gate->allows('update', $t), 'delete' => $gate->allows('delete', $t), 'assign' => $gate->allows('update', $t)],
        ];
    }
}
