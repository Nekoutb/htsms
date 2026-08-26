<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Delivers a single-use email sign-in link (magic link).
 */
final class LoginLinkNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $url,
        public readonly int $ttlMinutes,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your EA HTSMS sign-in link')
            ->greeting('Sign in to EA HTSMS')
            ->line('Use the button below to sign in. For your security this link can be used once and expires in '.$this->ttlMinutes.' minutes.')
            ->action('Sign in', $this->url)
            ->line('If you did not request this link you can safely ignore this email — no one can sign in without it.');
    }
}
