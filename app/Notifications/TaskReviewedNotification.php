<?php

namespace App\Notifications;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Notifications\Notification;

class TaskReviewedNotification extends Notification
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
            'message' => $this->task->status === TaskStatus::Done
                ? "{$this->task->code} \"{$this->task->title}\" dinyatakan Selesai"
                : "{$this->task->code} \"{$this->task->title}\" perlu direvisi",
            'task_id' => $this->task->id,
            'workspace_id' => $this->task->workspace_id,
            'status' => $this->task->status->value,
            'score' => $this->task->score,
            'feedback' => $this->task->feedback,
        ];
    }
}
