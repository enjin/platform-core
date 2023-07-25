<?php

namespace Enjin\Platform\Providers\Deferred;

use Enjin\Platform\Services\Qr\Interfaces\QrAdapterInterface;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class QrServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(
            QrAdapterInterface::class,
            config('enjin-platform.qr.adapter')
        );
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [QrAdapterInterface::class];
    }
}
