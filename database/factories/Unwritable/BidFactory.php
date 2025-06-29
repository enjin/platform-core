<?php

namespace Enjin\Platform\Database\Factories\Unwritable;

use Enjin\Platform\Models\Bid;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bid>
 */
class BidFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Bid::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'price' => fake()->numberBetween(1, 100),
            'height' => fake()->numberBetween(1, 100),
        ];
    }
}
