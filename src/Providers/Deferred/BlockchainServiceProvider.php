<?php

namespace Enjin\Platform\Providers\Deferred;

use Enjin\Platform\Enums\Global\ChainType;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Blockchain\Interfaces\BlockchainServiceInterface;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class BlockchainServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register()
    {
        $map = [
            ChainType::SUBSTRATE->value => Substrate::class,
        ];

        $driverKey = chain()->value;
        $driverClass = $map[$driverKey];
        $this->app->singleton(
            BlockchainServiceInterface::class,
            $driverClass,
        );
    }

    /**
     * Get the services provided by the provider.
     */
    #[\Override]
    public function provides()
    {
        return [BlockchainServiceInterface::class];
    }
}
