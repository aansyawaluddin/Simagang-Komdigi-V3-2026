<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ChannelController extends Controller
{
    /**
     * Channel milik user beserta jumlah pesan belum dibaca (untuk badge dan polling).
     * Admin yang memberi ?workspace_id= melihat semua channel workspace itu.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Workspace::class);

        $p = $request->validate([
            'workspace_id' => ['nullable', 'integer'],
            'include_archived' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $seesAll = ($user->isSuperAdmin() || $user->isAdminFull()) && ! empty($p['workspace_id']);

        $channels = Channel::query()
            ->with('workspace:id,type,name,is_archived')
            ->when(! $seesAll, fn ($q) => $q->whereHas('members', fn ($m) => $m->where('users.id', $user->id)))
            ->when($p['workspace_id'] ?? null, fn ($q, $id) => $q->where('workspace_id', $id))
            ->when(empty($p['include_archived']), fn ($q) => $q->where('is_archived', false))
            ->orderBy('workspace_id')
            ->orderBy('name')
            ->get()
            ->filter(fn (Channel $c) => Gate::forUser($user)->allows('view', $c))
            ->values();

        $unread = $this->unreadCounts($user, $channels->pluck('id')->all());

        $data = $channels->map(fn (Channel $c) => [
            'id' => $c->id,
            'name' => $c->name,
            'type' => $c->type,
            'workspace_id' => $c->workspace_id,
            'workspace_name' => $c->workspace?->name,
            'is_private' => $c->is_private,
            'is_archived' => $c->is_archived,
            'unread_count' => (int) ($unread[$c->id] ?? 0),
        ]);

        return response()->json([
            'data' => $data,
            'meta' => ['total_unread' => $data->sum('unread_count')],
        ]);
    }

    /** Buat channel topik. Anggota dipilih pembuat; kosong = semua anggota aktif workspace. */
    public function store(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('create', [Channel::class, $workspace]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9][a-z0-9\-]*$/',
                Rule::unique('channels', 'name')->where('workspace_id', $workspace->id)],
            'is_private' => ['nullable', 'boolean'],
            'member_ids' => ['nullable', 'array', 'max:200'],
            'member_ids.*' => ['integer', 'distinct'],
        ]);

        $workspaceUserIds = DB::table('workspace_members')
            ->where('workspace_id', $workspace->id)
            ->whereNull('left_at')
            ->pluck('user_id')
            ->all();

        $memberIds = $data['member_ids'] ?? $workspaceUserIds;

        if (array_diff($memberIds, $workspaceUserIds)) {
            throw ValidationException::withMessages(['member_ids' => 'Ada user yang bukan anggota workspace ini.']);
        }

        $channel = DB::transaction(function () use ($request, $workspace, $data, $memberIds) {
            $channel = Channel::create([
                'workspace_id' => $workspace->id,
                'name' => $data['name'],
                'type' => 'topic',
                'is_private' => $data['is_private'] ?? false,
                'created_by' => $request->user()->id,
            ]);

            $channel->members()->syncWithoutDetaching(array_unique([...$memberIds, $request->user()->id]));

            return $channel;
        });

        return response()->json(['data' => [
            'id' => $channel->id,
            'name' => $channel->name,
            'type' => $channel->type,
            'workspace_id' => $channel->workspace_id,
            'is_private' => $channel->is_private,
        ]], 201);
    }

    /** Arsipkan channel topik (read-only, pesan tetap tersimpan). */
    public function archive(Channel $channel): JsonResponse
    {
        $this->authorize('archive', $channel);

        if ($channel->type !== 'topic') {
            throw ValidationException::withMessages(['channel' => 'Hanya channel topik yang bisa diarsipkan.']);
        }

        $channel->update(['is_archived' => true]);

        return response()->json(['data' => ['id' => $channel->id, 'is_archived' => true]]);
    }

    /** Tandai channel sudah dibaca sampai pesan terbaru (atau sampai message_id). */
    public function markRead(Request $request, Channel $channel): JsonResponse
    {
        $this->authorize('view', $channel);

        $data = $request->validate([
            'message_id' => ['nullable', 'integer', Rule::exists('messages', 'id')->where('channel_id', $channel->id)],
        ]);

        $user = $request->user();
        $latest = Message::withTrashed()->where('channel_id', $channel->id)->max('id');
        $target = $latest ? min($data['message_id'] ?? $latest, $latest) : null;

        if ($target) {
            // Hanya maju, tidak pernah mundur. Non-anggota (admin yang melihat saja) tidak punya baris, jadi tidak berubah.
            DB::table('channel_members')
                ->where('channel_id', $channel->id)
                ->where('user_id', $user->id)
                ->where(fn ($q) => $q->whereNull('last_read_message_id')->orWhere('last_read_message_id', '<', $target))
                ->update(['last_read_message_id' => $target, 'updated_at' => now()]);
        }

        return response()->json([
            'unread_count' => (int) ($this->unreadCounts($user, [$channel->id])[$channel->id] ?? 0),
        ]);
    }

    /**
     * Pesan lain yang belum dibaca per channel, dihitung dari last_read_message_id.
     *
     * @param  array<int, int>  $channelIds
     * @return array<int, int>  channel_id => jumlah
     */
    private function unreadCounts(User $user, array $channelIds): array
    {
        if (! $channelIds) {
            return [];
        }

        return DB::table('messages')
            ->selectRaw('channel_id, count(*) as total')
            ->whereIn('channel_id', $channelIds)
            ->whereNull('deleted_at')
            ->where('user_id', '!=', $user->id)
            ->whereRaw(
                'id > coalesce((select cm.last_read_message_id from channel_members cm where cm.channel_id = messages.channel_id and cm.user_id = ?), 0)',
                [$user->id]
            )
            ->groupBy('channel_id')
            ->pluck('total', 'channel_id')
            ->all();
    }
}
