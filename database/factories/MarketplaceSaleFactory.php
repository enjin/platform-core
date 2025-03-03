<?php

namespace Enjin\Platform\Database\Factories;

use Enjin\Platform\Models\MarketplaceSale;
use Illuminate\Database\Eloquent\Factories\Factory;

class MarketplaceSaleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = MarketplaceSale::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'price' => fake()->numberBetween(1, 100),
            'amount' => fake()->numberBetween(1, 100),
        ];
    }
}
