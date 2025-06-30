<?php

namespace Enjin\Platform\Database\Factories\Unwritable;

use Enjin\Platform\Enums\Substrate\ListingState;
use Enjin\Platform\Models\MarketplaceState;
use Illuminate\Database\Eloquent\Factories\Factory;

class MarketplaceStateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = MarketplaceState::class;

    /**
     * Define the model's default state.
     */
    public function definition()
    {
        return [
            'state' => ListingState::caseNamesAsCollection()->random(),
            'height' => fake()->numberBetween(1, 100),
        ];
    }
}
