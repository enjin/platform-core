<?php

namespace Enjin\Platform\Database\Factories;

use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollectionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var Collection
     */
    protected $model = Collection::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'collection_chain_id' => (string) fake()->unique()->numberBetween(2000),
            'owner_wallet_id' => Wallet::factory(),
            'max_token_count' => fake()->numberBetween(1),
            'max_token_supply' => (string) fake()->numberBetween(1),
            'force_single_mint' => fake()->boolean(),
            'is_frozen' => false,
            'token_count' => '0',
            'attribute_count' => '0',
            'total_deposit' => '0',
            'network' => 'developer',
        ];
    }
}
