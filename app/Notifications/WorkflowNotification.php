<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkflowNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param list<string> $channels */
    public function __construct(
        public string $event,
        public string $title,
        public string $message,
        public string $actionUrl,
        public string $actionLabel,
        public array $channels,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        abort_unless($notifiable instanceof User, 500);

        return (new MailMessage)
            ->subject($this->title)
            ->greeting('Halo '.$notifiable->name)
            ->line($this->message)
            ->action($this->actionLabel, url($this->actionUrl))
            ->line('Anda menerima pesan ini karena ada pembaruan pada MagangHub.');
    }

    /** @return array{event: string, title: string, body: string, message: string, action_url: string, action_label: string} */
    public function toDatabase(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'title' => $this->title,
            'body' => $this->message,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'action_label' => $this->actionLabel,
        ];
    }
}
