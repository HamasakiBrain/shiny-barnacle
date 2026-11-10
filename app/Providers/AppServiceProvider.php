<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\Contracts\UserRepositoryInterface::class,
            \App\Repositories\Eloquent\UserRepository::class,
        );
        $this->app->bind(
            \App\Repositories\Contracts\CatalogRepositoryInterface::class,
            \App\Repositories\Eloquent\CatalogRepository::class,
        );
        $this->app->bind(
            \App\Repositories\Contracts\CartRepositoryInterface::class,
            \App\Repositories\Eloquent\CartRepository::class,
        );
        $this->app->bind(
            \App\Repositories\Contracts\OrderRepositoryInterface::class,
            \App\Repositories\Eloquent\OrderRepository::class,
        );
        $this->app->bind(
            \App\Repositories\Contracts\EngagementRepositoryInterface::class,
            \App\Repositories\Eloquent\EngagementRepository::class,
        );
    }

    public function boot(): void
    {
        Gate::define('admin', fn (User $user) => $user->role === Role::Admin);
        RateLimiter::for(
            'api',
            fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?? $request->ip()),
        );
        RateLimiter::for('auth', fn (Request $request) => [
            Limit::perMinute(10)->by('auth-ip:' . $request->ip()),
            Limit::perMinute(5)->by('auth-email:' . strtolower((string) $request->input('email'))),
        ]);
    }
}
