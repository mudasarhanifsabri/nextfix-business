<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $title, public string $message, public string $url, public string $level = 'info') {}

    public function via(object $notifiable): array
    {
        return app()->environment('production') && config('mail.default') !== 'log' ? ['database', 'mail'] : ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => $this->title, 'message' => $this->message, 'url' => $this->url, 'level' => $this->level];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject($this->title)->greeting('Hello '.$notifiable->name.',')->line($this->message)->action('Open in NextFix', $this->url);
    }
}
