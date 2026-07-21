<?php

declare(strict_types=1);

namespace App\Domain\Integration;

enum ApiKeyAbility: string
{
    case DevicesRead = 'devices:read';
    case DevicesWrite = 'devices:write';
    case MessagesRead = 'messages:read';
    case MessagesWrite = 'messages:write';
    case WebhooksRead = 'webhooks:read';
    case WebhooksWrite = 'webhooks:write';
    case ContactsRead = 'contacts:read';
    case ContactsWrite = 'contacts:write';
    case CampaignsRead = 'campaigns:read';
    case CampaignsWrite = 'campaigns:write';
}
