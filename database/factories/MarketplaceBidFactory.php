<?php

namespace Enjin\Platform\Database\Factories;

use Enjin\Platform\Models\MarketplaceBid;
use Illuminate\Database\Eloquent\Factories\Factory;

class MarketplaceBidFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = MarketplaceBid::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'price' => fake()->numberBetween(1, 100),
            'height' => fake()->numberBetween(1, 100),
        ];
    }
}
