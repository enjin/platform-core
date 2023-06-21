<?php

namespace Enjin\Platform\Providers;

use Enjin\Platform\Services\Auth\AuthManager;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        $this->app->singleton(AuthManager::class, function ($app) {
            return new AuthManager($app);
        });
    }
}
