<?php

namespace Enjin\Platform\Database\Factories\Unwritable;

use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Wallet;
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
            'id' => (string) $collectionId = fake()->unique()->numberBetween(2000),
            'collection_id' => $collectionId,
            'owner_id' => Wallet::factory(),
            //            'pending_transfer' => null,
            //            'max_token_count' => fake()->numberBetween(1),
            //            'max_token_supply' => (string) fake()->numberBetween(1),
            //            'force_collapsing_supply' => fake()->boolean(),
            //            'is_frozen' => false,
            //            'token_count' => '0',
            //            'attribute_count' => '0',
            //            'total_deposit' => '0',
            //            'total_infusion' => '0',
            //            'network' => 'local',
        ];
    }
}
