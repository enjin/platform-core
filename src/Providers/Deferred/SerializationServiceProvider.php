<?php

namespace Enjin\Platform\Providers\Deferred;

use Enjin\Platform\Enums\Global\ChainType;
use Enjin\Platform\Services\Serialization\Implementations\Substrate;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class SerializationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        $map = [
            ChainType::SUBSTRATE->value => Substrate::class,
        ];

        $driverKey = chain()->value;
        $driverClass = $map[$driverKey];
        $this->app->singleton(
            SerializationServiceInterface::class,
            $driverClass
        );
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides()
    {
        return [SerializationServiceInterface::class];
    }
}
