<?php

declare(strict_types=1);

namespace App\Domain\Integration;

enum WebhookEvent: string
{
    case MessageReceived = 'message.received';
    case MessageSent = 'message.sent';
    case MessageDelivered = 'message.delivered';
    case MessageFailed = 'message.failed';
    case MessageExpired = 'message.expired';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
