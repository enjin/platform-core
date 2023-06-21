<?php

namespace Enjin\Platform\Database\Factories;

use Enjin\Platform\Models\Block;
use Illuminate\Database\Eloquent\Factories\Factory;

class BlockFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var Block
     */
    protected $model = Block::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'number' => random_int(1, 1000),
            'hash' => $this->faker->unique()->public_key(),
        ];
    }
}
