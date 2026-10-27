<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Notifications\Notification;

class TaskReviewRequestedNotification extends Notification
{
    public function __construct(private Task $task, private User $actor)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "{$this->actor->name} mengirim {$this->task->code} \"{$this->task->title}\" untuk direview",
            'task_id' => $this->task->id,
            'workspace_id' => $this->task->workspace_id,
        ];
    }
}
