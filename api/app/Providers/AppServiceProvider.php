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

        // Rate limit for login/register attempts
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)  // Only 5 attempts per minute
                ->by($request->ip())     // Per IP address
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many login attempts. Please try again later.',
                    ], 429);
                });
        });

        Relation::enforceMorphMap([
            'user' => \App\Models\User::class,
            'channel' => \App\Models\Channel::class,
            'conversation' => \App\Models\Conversation::class,
        ]);
    }
}
