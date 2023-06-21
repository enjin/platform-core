<?php

namespace Enjin\Platform\Database\Factories;

use Enjin\Platform\Models\CollectionAccount;
use Enjin\Platform\Models\CollectionAccountApproval;
use Enjin\Platform\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollectionAccountApprovalFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var CollectionAccountApproval
     */
    protected $model = CollectionAccountApproval::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'collection_account_id' => CollectionAccount::factory(),
            'wallet_id' => Wallet::factory(),
            'expiration' => fake()->numberBetween(1),
        ];
    }
}
