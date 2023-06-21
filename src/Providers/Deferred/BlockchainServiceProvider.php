<?php

namespace Enjin\Platform\Providers\Deferred;

use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Blockchain\Interfaces\BlockchainServiceInterface;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class BlockchainServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        $map = [
            'substrate' => Substrate::class,
        ];

        $driverKey = config('enjin-platform.chains.selected');
        $driverClass = $map[$driverKey];
        $this->app->singleton(
            BlockchainServiceInterface::class,
            $driverClass,
        );
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides()
    {
        return [BlockchainServiceInterface::class];
    }
}
