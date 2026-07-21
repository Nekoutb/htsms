<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        RateLimiter::for('api', static function (Request $request): Limit {
            $user = $request->user();
            $identity = $user instanceof User
                ? 'user:'.$user->email
                : 'ip:'.($request->ip() ?? 'unknown');

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
    }
}
