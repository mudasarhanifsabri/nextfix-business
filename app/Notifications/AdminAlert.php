<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminAlert extends Notification
{
    public function __construct(
        public string $title,
        public string $message,
        public string $url,
        public string $level = 'info',
        public string $actionLabel = 'Open in NextFix',
        public array $details = [],
        public ?string $eventKey = null,
        public bool $email = true,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        $mailer = (string) config('mail.default');

        if ($this->email && filled($notifiable->email ?? null) && ! in_array($mailer, ['log', 'array'], true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'level' => $this->level,
            'action_label' => $this->actionLabel,
            'event_key' => $this->eventKey,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title.' | NextFix')
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->message);

        foreach ($this->details as $label => $value) {
            if (filled($value)) {
                $mail->line('**'.$label.':** '.$value);
            }
        }

        return $mail
            ->action($this->actionLabel, $this->url)
            ->line('This is an automatic service update from NextFix Business.');
    }
}
