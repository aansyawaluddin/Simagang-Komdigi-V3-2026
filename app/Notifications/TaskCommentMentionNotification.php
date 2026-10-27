<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Notifications\Notification;

class TaskCommentMentionNotification extends Notification
{
    public function __construct(private Task $task, private TaskComment $comment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "{$this->comment->user?->name} menyebut kamu di komentar {$this->task->code} \"{$this->task->title}\"",
            'task_id' => $this->task->id,
            'workspace_id' => $this->task->workspace_id,
            'comment_id' => $this->comment->id,
        ];
    }
}
