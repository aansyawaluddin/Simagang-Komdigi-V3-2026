<?php

namespace App\Notifications;

use App\Models\Intern;
use App\Models\Team;
use Illuminate\Notifications\Notification;

class InternJoinedTeamNotification extends Notification
{
    public function __construct(private Intern $intern, private Team $team)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Intern baru {$this->intern->name} masuk tim {$this->team->name}",
            'intern_id' => $this->intern->id,
            'team_id' => $this->team->id,
        ];
    }
}
