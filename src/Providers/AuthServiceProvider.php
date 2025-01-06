<?php

namespace Enjin\Platform\Providers;

use Enjin\Platform\Services\Auth\AuthManager;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register()
    {
        $this->app->singleton(AuthManager::class, fn ($app) => new AuthManager($app));
    }
}
