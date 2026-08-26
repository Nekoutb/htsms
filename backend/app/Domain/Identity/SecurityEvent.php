<?php

declare(strict_types=1);

namespace App\Domain\Identity;

enum SecurityEvent: string
{
    case Registered = 'identity.registered';
    case LoginSucceeded = 'identity.login_succeeded';
    case LoginFailed = 'identity.login_failed';
    case LoggedOut = 'identity.logged_out';
    case VerificationSent = 'identity.verification_sent';
    case EmailVerified = 'identity.email_verified';
    case MagicLinkRequested = 'identity.magic_link_requested';
    case MagicLinkConsumed = 'identity.magic_link_consumed';
}
