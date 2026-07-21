<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AdminMfaCode extends Notification
{
    use Queueable;

    public function __construct(public readonly string $code) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your HTSMS admin verification code')
            ->line('Use this one-time code to access the HTSMS platform administration area:')
            ->line($this->code)
            ->line('This code expires in 10 minutes. If you did not request it, change your password immediately.');
    }
}
