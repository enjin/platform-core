<?php

namespace Enjin\Platform\Database\Factories;

use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Models\TokenAccountApproval;
use Enjin\Platform\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

class TokenAccountApprovalFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var TokenAccountApproval
     */
    protected $model = TokenAccountApproval::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'token_account_id' => TokenAccount::factory(),
            'wallet_id' => Wallet::factory(),
            'amount' => (string) fake()->numberBetween(1),
            'expiration' => fake()->numberBetween(1),
        ];
    }
}
