<?php

declare(strict_types=1);

namespace App\DTO\Messaging;

use App\Models\Message;

final readonly class SubmittedMessage
{
    public function __construct(public Message $message, public bool $created) {}
}
