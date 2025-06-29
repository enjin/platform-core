<?php

namespace Enjin\Platform\Database\Factories\Unwritable;

use Enjin\Platform\Models\Block;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Block>
 */
class BlockFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Block::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => $this->faker->numberBetween(1, 1000000),
            'hash' => $this->faker->unique()->public_key(),
        ];
    }
}
