<?php

namespace App\Notifications;

use App\Models\Intern;
use Illuminate\Notifications\Notification;

class InternWithoutMentorNotification extends Notification
{
    public function __construct(private Intern $intern)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Intern aktif {$this->intern->name} belum punya mentor",
            'intern_id' => $this->intern->id,
        ];
    }
}
