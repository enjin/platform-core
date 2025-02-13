<?php

namespace Enjin\Platform;

use Enjin\Platform\FuelTanks\Package as FuelTanksPackage;
use Enjin\Platform\FuelTanks\Services\Processor\Substrate\Codec\Encoder as FuelTankEncoder;
use Enjin\Platform\Services\Processor\Substrate\Codec\Encoder as BaseEncoder;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FuelTanksServiceProvider extends PackageServiceProvider
{
    /**
     * Configure provider.
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('platform-fuel-tanks')
            ->hasConfigFile(['enjin-platform-fuel-tanks'])
            ->hasMigrations(
                'create_fuel_tanks_table',
                'create_fuel_tank_accounts_table',
                'create_fuel_tank_rules_table',
                'add_total_received_to_accounts_table'
            )
            ->hasTranslations();
    }

    /**
     * Register provider.
     *
     * @return void
     */
    #[\Override]
    public function register()
    {
        if (app()->runningUnitTests()) {
            FuelTanksPackage::setPath(__DIR__ . '/..');
        }

        parent::register();

        BaseEncoder::setCallIndexKeys(array_merge(BaseEncoder::getCallIndexKeys(), FuelTankEncoder::getCallIndexKeys()));
    }

    /**
     * Boot provider.
     *
     * @return void
     */
    #[\Override]
    public function boot()
    {
        parent::boot();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    public function packageRegistered()
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'enjin-platform-fuel-tanks');
    }
}
