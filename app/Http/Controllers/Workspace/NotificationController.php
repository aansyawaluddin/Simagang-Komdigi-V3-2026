<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

/** Lonceng notifikasi in-app. */
class NotificationController extends Controller
{
    private const DEFAULT_PER_PAGE = 10;
    private const MAX_PER_PAGE = 50;

    /** Jumlah belum dibaca + notifikasi terbaru (default 10), untuk polling lonceng. */
    public function index(Request $request): JsonResponse
    {
        $p = $request->validate([
            'per_page' => ['nullable', 'integer', 'between:1,' . self::MAX_PER_PAGE],
            'unread' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();

        $page = $user->notifications()
            ->when(! empty($p['unread']), fn ($q) => $q->whereNull('read_at'))
            ->paginate($p['per_page'] ?? self::DEFAULT_PER_PAGE);

        return response()->json([
            'data' => $page->getCollection()->map(fn (DatabaseNotification $n) => $this->present($n))->values(),
            'meta' => [
                'unread_count' => $user->unreadNotifications()->count(),
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $user->notifications()->whereKey($id)->firstOrFail()->markAsRead();

        return response()->json(['unread_count' => $user->unreadNotifications()->count()]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['unread_count' => 0]);
    }

    private function present(DatabaseNotification $n): array
    {
        $data = $n->data;

        return [
            'id' => $n->id,
            'type' => Str::snake(Str::beforeLast(class_basename($n->type), 'Notification')),
            'message' => $data['message'] ?? '',
            'data' => collect($data)->except('message')->all(),
            'read_at' => $n->read_at,
            'created_at' => $n->created_at,
        ];
    }
}
