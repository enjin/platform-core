<?php

namespace Enjin\Platform\Database\Factories;

use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\CollectionAccount;
use Enjin\Platform\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollectionAccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var CollectionAccount
     */
    protected $model = CollectionAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'collection_id' => Collection::factory(),
            'wallet_id' => Wallet::factory(),
            'is_frozen' => false,
            'account_count' => 0,
        ];
    }
}
