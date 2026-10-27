<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskCommentController extends Controller
{
    public function __construct(private TaskService $tasks)
    {
    }

    public function store(Request $request, Task $task): JsonResponse
    {
        $this->authorize('comment', $task);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => AttachmentController::FILE_RULES,
        ]);

        $comment = DB::transaction(function () use ($request, $task, $data) {
            $comment = $task->comments()->create([
                'user_id' => $request->user()->id,
                'body' => $data['body'],
            ]);

            if ($request->hasFile('files')) {
                $this->tasks->storeAttachments($comment, $request->file('files'), $request->user());
            }

            $this->tasks->record($task, $request->user(), 'commented', ['comment_id' => $comment->id]);

            return $comment;
        });

        $comment->load(['user', 'attachments']);

        return response()->json([
            'id' => $comment->id,
            'body' => $comment->body,
            'user' => ['id' => $comment->user->id, 'name' => $comment->user->name],
            'attachments' => $comment->attachments->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->original_name,
                'url' => route('workspace.attachments.download', $a),
            ]),
            'created_at' => $comment->created_at,
        ], 201);
    }
}
