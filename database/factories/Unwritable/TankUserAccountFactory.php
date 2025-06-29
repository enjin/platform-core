<?php

namespace Enjin\Platform\Database\Factories\Unwritable;

use Enjin\Platform\Models\TankUserAccount;
use Enjin\Platform\Models\Wallet;
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
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wallet_id' => Wallet::factory()->create(),
        ];
    }
}
