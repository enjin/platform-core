<?php

namespace Enjin\Platform\Providers;

use Enjin\Platform\Services\Auth\AuthManager;
use Illuminate\Support\ServiceProvider;
use Override;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        $this->app->singleton(AuthManager::class, fn ($app) => new AuthManager($app));
    }
}
