<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Verifies a Cloudflare Turnstile token on signup and login forms. When no
 * secret is configured the challenge is skipped so the forms keep working
 * until real keys are supplied.
 */
final class VerifyTurnstile
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config()->string('services.turnstile.secret', '');
        if ($secret === '') {
            return $next($request);
        }

        $token = $request->string('cf-turnstile-response')->toString();
        $verified = false;
        if ($token !== '') {
            try {
                $response = Http::asForm()->timeout(8)->post(
                    'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                    ['secret' => $secret, 'response' => $token, 'remoteip' => $request->ip()],
                );
                $verified = $response->successful() && $response->json('success') === true;
            } catch (Throwable $exception) {
                report($exception);
                $verified = false;
            }
        }

        if (! $verified) {
            throw ValidationException::withMessages([
                'captcha' => [__('Please complete the verification challenge and try again.')],
            ]);
        }

        return $next($request);
    }
}
