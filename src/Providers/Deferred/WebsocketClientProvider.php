<?php

namespace Enjin\Platform\Providers\Deferred;

use Enjin\Platform\Clients\Abstracts\WebsocketAbstract;
use Enjin\Platform\Clients\Implementations\SubstrateSocketClient;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class WebsocketClientProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        $map = [
            chain()->value => SubstrateSocketClient::class,
        ];

        $driverKey = chain()->value;
        $driverClass = $map[$driverKey];
        $this->app->singleton(
            WebsocketAbstract::class,
            $driverClass,
        );
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides()
    {
        return [WebsocketAbstract::class];
    }
}
