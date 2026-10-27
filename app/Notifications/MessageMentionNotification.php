<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Notifications\Notification;

class MessageMentionNotification extends Notification
{
    public function __construct(private Message $message)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $channel = $this->message->channel;

        return [
            'message' => "{$this->message->user?->name} menyebut kamu di #{$channel->name}",
            'channel_id' => $channel->id,
            'workspace_id' => $channel->workspace_id,
            'message_id' => $this->message->id,
        ];
    }
}
