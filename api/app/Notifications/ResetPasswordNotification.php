<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $token,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = config('app.frontend_url').'/reset-password?token='.$this->token.'&email='.urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Reset Password')
            ->markdown('emails.reset-password', [
                'userName' => $notifiable->name,
                'url' => $url,
                'expiry' => config('auth.passwords.users.expire'),
                'appName' => config('app.name'),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
