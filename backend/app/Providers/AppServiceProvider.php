<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\DeveloperApiKey;
use App\Models\Device;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $applicationKey = config('app.key');
        if (! is_string($applicationKey) || $applicationKey === '') {
            throw new RuntimeException('APP_KEY must be configured.');
        }

        Model::preventLazyLoading(! $this->app->isProduction());
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        RateLimiter::for('api', static function (Request $request): Limit {
            $user = $request->user();
            $apiKey = $request->attributes->get('developer_api_key');
            $device = $request->attributes->get('device');
            $identity = match (true) {
                $user instanceof User => 'user:'.$user->email,
                $apiKey instanceof DeveloperApiKey => 'api-key:'.$apiKey->id,
                $device instanceof Device => 'device:'.$device->id,
                default => 'ip:'.($request->ip() ?? 'unknown'),
            };

            return Limit::perMinute(120)->by($identity);
        });

        RateLimiter::for('auth-login', static function (Request $request): Limit {
            $email = $request->string('email')->trim()->lower()->toString();

            return Limit::perMinute(5)->by(hash('sha256', $email.'|'.($request->ip() ?? 'unknown')));
        });

        RateLimiter::for('auth-register', static fn (Request $request): Limit => Limit::perHour(5)
            ->by($request->ip() ?? 'unknown'));

        RateLimiter::for('password-reset', static fn (Request $request): Limit => Limit::perHour(5)
            ->by($request->ip() ?? 'unknown'));

        RateLimiter::for('device-pairing', static fn (Request $request): Limit => Limit::perMinute(10)
            ->by($request->ip() ?? 'unknown'));

        RateLimiter::for('device-heartbeat', static function (Request $request): Limit {
            $device = $request->attributes->get('device');
            $identity = $device instanceof Device ? $device->id : ($request->ip() ?? 'unknown');

            return Limit::perMinute(12)->by($identity);
        });
    }
}
