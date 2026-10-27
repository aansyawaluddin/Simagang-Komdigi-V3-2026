<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class AnnouncementNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(private Message $message)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Pengumuman baru dari {$this->message->user->name}: " . \Illuminate\Support\Str::limit($this->message->body, 80),
            'channel_id' => $this->message->channel_id,
            'message_id' => $this->message->id,
        ];
    }
}
