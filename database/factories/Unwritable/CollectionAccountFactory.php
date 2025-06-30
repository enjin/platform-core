<?php

namespace Enjin\Platform\Database\Factories\Unwritable;

use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Indexer\CollectionAccount;
use Enjin\Platform\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CollectionAccount>
 */
class CollectionAccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = CollectionAccount::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'account_id' => $wallet = Wallet::factory()->create(),
            'collection_id' => $collection = Collection::factory()->create(),

            'id' => $wallet->id . '-' . $collection->id,
            'is_frozen' => false,
            'account_count' => 0,
            'approvals' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
