<?php

namespace App\Providers;

use App\Models\Profile;
use App\Observers\ProfileObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Request::macro('currentProfile', function (): Profile {
            /** @var Request $this */
            return $this->user()->profile;
        });
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Profile::observe(ProfileObserver::class);

        // Default API rate limiter (required for throttleApi())
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?? $request->ip());
        });

        // Rate limit for login/register/forgot-pass attempts
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinutes(30, 10)  // Only 10 attempts per 30 minutes
                ->by($request->ip())     // Per IP address
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many attempts. Please try again later.',
                    ], 429);
                });
        });

        Relation::enforceMorphMap([
            'user' => \App\Models\User::class,
            'channel' => \App\Models\Channel::class,
            'conversation' => \App\Models\Conversation::class,
        ]);

        /* making it through ResetPasswordNotification
         * ResetPassword::createUrlUsing(function (User $user, string $token) {
            return config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . $user->email;
        });*/
    }
}
