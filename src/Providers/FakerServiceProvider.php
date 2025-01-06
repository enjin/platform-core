<?php

namespace Enjin\Platform\Providers;

use Enjin\Platform\Providers\Faker\Erc1155Provider;
use Enjin\Platform\Providers\Faker\SubstrateProvider;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Support\ServiceProvider;

class FakerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register()
    {
        $this->app->singleton(Generator::class, function () {
            $faker = Factory::create();
            $faker->addProvider(new SubstrateProvider($faker));
            $faker->addProvider(new Erc1155Provider($faker));

            return $faker;
        });
    }
}
