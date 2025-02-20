<?php

namespace Enjin\Platform\Database\Factories;

use Enjin\Platform\Models\FuelTankAccount;
use Enjin\Platform\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

class FuelTankAccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = FuelTankAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'wallet_id' => Wallet::factory()->create(),
        ];
    }
}
