<?php

namespace Enjin\Platform\Database\Factories\Unwritable;

use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\CollectionAccount;
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
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'collection_id' => Collection::factory(),
            'wallet_id' => Wallet::factory(),
            'is_frozen' => false,
            'account_count' => 0,
        ];
    }
}
