<?php

namespace Enjin\Platform\Database\Factories\Unwritable;

use Enjin\Platform\Enums\Substrate\CoveragePolicy;
use Enjin\Platform\Models\Indexer\FuelTank;
use Enjin\Platform\Providers\Faker\SubstrateProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FuelTank>
 */
class FuelTankFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = FuelTank::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->text(32),
            'public_key' => resolve(SubstrateProvider::class)->public_key(),
            'reserves_account_creation_deposit' => fake()->boolean(),
            'coverage_policy' => fake()->randomElement(CoveragePolicy::caseNamesAsArray()),
        ];
    }
}
