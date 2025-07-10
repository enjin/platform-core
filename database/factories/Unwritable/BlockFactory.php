<?php

namespace Enjin\Platform\Database\Factories\Unwritable;

use Enjin\Platform\Models\Indexer\Block;
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
     */
    public function definition(): array
    {
        return [
            'id' => $id = $this->faker->unique()->public_key(),
            'spec_version' => $this->faker->randomNumber(),
            'transaction_version' => $this->faker->randomNumber(),
            'genesis_hash' => $this->faker->unique()->public_key(),
            'block_hash' => $id,
            'block_number' => $this->faker->randomNumber(),
            'existential_deposit' => $this->faker->randomNumber(),
            'timestamp' => now(),
            'validator' => null,
            'marketplace' => null,
        ];
    }
}
