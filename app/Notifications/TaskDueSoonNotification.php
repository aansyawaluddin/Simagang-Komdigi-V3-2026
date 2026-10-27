<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Notification;

class TaskDueSoonNotification extends Notification
{
    public function __construct(private Task $task)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Deadline {$this->task->code} \"{$this->task->title}\" kurang dari 24 jam lagi",
            'task_id' => $this->task->id,
            'workspace_id' => $this->task->workspace_id,
            'due_at' => $this->task->due_at?->toIso8601String(),
        ];
    }
}
