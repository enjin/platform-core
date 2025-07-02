<?php

namespace Enjin\Platform\Database\Factories\Unwritable;

use Enjin\Platform\Models\Indexer\Account;
use Enjin\Platform\Models\Indexer\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Collection>
 */
class CollectionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Collection::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'collection_id' => $collectionId = fake()->unique()->numberBetween(2000),
            'id' => (string) $collectionId,

            'owner_id' => Account::factory(),
            'mint_policy' => [
                'maxTokenCount' => (string) fake()->numberBetween(1),
                'maxTokenSupply' => (string) fake()->numberBetween(1),
                'forceSingleMint' => fake()->boolean(),
            ],
            'market_policy' => null,
            'burn_policy' => null,
            'transfer_policy' => [
                'isFrozen' => false,
            ],
            'attribute_policy' => null,
            'attribute_count' => 0,
            'total_deposit' => 0,
            'created_at' => now(),
            // Fields from the indexer that we might not need
            'name' => null,
            'metadata' => null,
            'flags' => json_encode([]),
            'socials' => json_encode([]),
            'category' => null,
            'verified_at' => null,
            'hidden' => false,
            'stats' => null,
            // TODO: Verify if the following are needed
            // 'pending_transfer' => null,
            // 'token_count' => '0',
            // 'network' => 'local',
        ];
    }
}
