<?php

namespace Enjin\Platform\Database\Factories\Unwritable;

use Enjin\Platform\Models\Indexer\Account;
use Enjin\Platform\Models\Indexer\TankUserAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TankUserAccount>
 */
class TankUserAccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = TankUserAccount::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'wallet_id' => Account::factory()->create(),
        ];
    }
}
