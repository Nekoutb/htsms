<?php

use App\Exceptions\SubscriptionLimitException;
use App\Http\Middleware\AuthenticateDeveloperApiKey;
use App\Http\Middleware\AuthenticateDevice;
use App\Http\Middleware\RequireDeveloperApiKeyAbility;
use App\Http\Middleware\RequirePlatformAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'developer-api-key' => AuthenticateDeveloperApiKey::class,
            'developer-ability' => RequireDeveloperApiKeyAbility::class,
            'device-auth' => AuthenticateDevice::class,
            'platform-admin' => RequirePlatformAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (SubscriptionLimitException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 402);
            }

            return back()->withErrors(['subscription' => $exception->getMessage()]);
        });
    })->create();
