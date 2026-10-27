<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Channel;
use App\Models\Message;
use App\Notifications\AnnouncementNotification;
use App\Services\AttachmentService;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    private const PAGE_SIZE = 50;
    private const POLL_LIMIT = 200;

    public function __construct(private MessageService $messages, private AttachmentService $attachments)
    {
    }

    /**
     * Daftar pesan channel.
     *  - default / ?before=ID : 50 pesan utama terbaru (urut lama ke baru), scroll ke atas dengan before
     *  - ?after=ID            : polling, semua pesan baru termasuk balasan thread
     *  - ?parent_id=ID        : balasan satu thread
     *  - ?q=kata / ?pinned=1  : pencarian / pesan yang di-pin
     */
    public function index(Request $request, Channel $channel): AnonymousResourceCollection
    {
        $this->authorize('view', $channel);

        $p = $request->validate([
            'after' => ['nullable', 'integer', 'min:0'],
            'before' => ['nullable', 'integer', 'min:1'],
            'parent_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
            'pinned' => ['nullable', 'boolean'],
        ]);

        $base = fn () => Message::withTrashed()
            ->where('channel_id', $channel->id)
            ->with(['user:id,name', 'mentions:id,name', 'attachments'])
            ->withCount('replies');

        $hasMore = false;

        if (isset($p['after'])) {
            $messages = $base()->where('id', '>', $p['after'])->orderBy('id')->limit(self::POLL_LIMIT)->get();
        } elseif (isset($p['parent_id'])) {
            $messages = $base()->where('parent_id', $p['parent_id'])->orderBy('id')->limit(self::POLL_LIMIT)->get();
        } elseif (! empty($p['q'])) {
            $like = '%' . addcslashes($p['q'], '%_\\') . '%';
            $messages = $base()->withoutTrashed()->where('body', 'like', $like)->orderByDesc('id')->limit(self::PAGE_SIZE)->get()->reverse()->values();
        } elseif (! empty($p['pinned'])) {
            $messages = $base()->withoutTrashed()->where('is_pinned', true)->orderByDesc('id')->limit(self::PAGE_SIZE)->get()->reverse()->values();
        } else {
            $page = $base()
                ->whereNull('parent_id')
                ->when($p['before'] ?? null, fn ($q, $before) => $q->where('id', '<', $before))
                ->orderByDesc('id')
                ->limit(self::PAGE_SIZE + 1)
                ->get();

            $hasMore = $page->count() > self::PAGE_SIZE;
            $messages = $page->take(self::PAGE_SIZE)->reverse()->values();
        }

        $this->attachTaskRefs($channel, $messages);

        return MessageResource::collection($messages)->additional(['meta' => [
            'has_more' => $hasMore,
            'latest_id' => $messages->max('id'),
        ]]);
    }

    /** Kirim pesan atau balas thread (parent_id). Teks, mention, dan lampiran. */
    public function store(Request $request, Channel $channel): JsonResponse
    {
        $this->authorize('post', $channel);

        $data = $request->validate([
            'body' => ['required_without:files', 'nullable', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer', Rule::exists('messages', 'id')
                ->where('channel_id', $channel->id)
                ->whereNull('parent_id')
                ->whereNull('deleted_at')],
            'mention_ids' => ['nullable', 'array', 'max:50'],
            'mention_ids.*' => ['integer'],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => AttachmentController::FILE_RULES,
        ]);

        $body = trim($data['body'] ?? '');

        if ($this->messages->mentionsChannel($body)) {
            $this->authorize('mentionAll', $channel);
        }

        $user = $request->user();

        $message = DB::transaction(function () use ($request, $channel, $user, $data, $body) {
            $message = $channel->messages()->create([
                'user_id' => $user->id,
                'parent_id' => $data['parent_id'] ?? null,
                'body' => $body,
            ]);

            if ($request->hasFile('files')) {
                $this->attachments->store($message, $request->file('files'), $user, compressImages: true);
            }

            $this->messages->syncMentions($message, $user, $data['mention_ids'] ?? []);

            return $message;
        });

        // Pengumuman baru: semua anggota channel kecuali penulis dan yang sudah di-mention.
        if ($channel->type === 'announcement' && ! $message->parent_id) {
            $mentioned = $message->mentions()->pluck('users.id');

            Notification::send(
                $channel->members()->where('users.id', '!=', $user->id)->whereNotIn('users.id', $mentioned)->get(),
                new AnnouncementNotification($message->setRelation('user', $user))
            );
        }

        return $this->respond($channel, $message, 201);
    }

    /** Edit pesan sendiri; diberi tanda "diedit". */
    public function update(Request $request, Message $message): JsonResponse
    {
        // Gate::before melewatkan super admin di Policy, tapi edit tetap hanya untuk penulis.
        abort_unless($message->user_id === $request->user()->id, 403);
        $this->authorize('update', $message);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'mention_ids' => ['nullable', 'array', 'max:50'],
            'mention_ids.*' => ['integer'],
        ]);

        $body = trim($data['body']);

        if ($this->messages->mentionsChannel($body)) {
            $this->authorize('mentionAll', $message->channel);
        }

        DB::transaction(function () use ($request, $message, $body, $data) {
            if ($body !== $message->body) {
                $message->update(['body' => $body, 'edited_at' => now()]);
            }

            $this->messages->syncMentions($message, $request->user(), $data['mention_ids'] ?? []);
        });

        return $this->respond($message->channel, $message);
    }

    /** Soft delete: pesan tampil sebagai "pesan dihapus". */
    public function destroy(Message $message): JsonResponse
    {
        $this->authorize('delete', $message);

        $message->delete();

        return response()->json(null, 204);
    }

    /** Pin / lepas pin pesan: moderator saja. */
    public function pin(Request $request, Message $message): JsonResponse
    {
        $this->authorize('pin', $message);

        $data = $request->validate(['is_pinned' => ['required', 'boolean']]);

        $message->update(['is_pinned' => $data['is_pinned']]);

        return $this->respond($message->channel, $message);
    }

    private function respond(Channel $channel, Message $message, int $status = 200): JsonResponse
    {
        $message = Message::withTrashed()
            ->with(['user:id,name', 'mentions:id,name', 'attachments'])
            ->withCount('replies')
            ->findOrFail($message->id);

        $this->attachTaskRefs($channel, collect([$message]));

        return (new MessageResource($message))->response()->setStatusCode($status);
    }

    /** @param  Collection<int, Message>  $messages */
    private function attachTaskRefs(Channel $channel, Collection $messages): void
    {
        $refs = $this->messages->taskRefs($channel, $messages);

        foreach ($messages as $message) {
            $message->setAttribute('task_refs', $refs[$message->id] ?? []);
        }
    }
}
