<?php

namespace Enjin\Platform\Database\Factories;

use Enjin\Platform\Models\Indexer\Account;
use Enjin\Platform\Models\Indexer\TokenAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class TokenAccountApprovalFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
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
            'wallet_id' => Account::factory(),
            'amount' => (string) fake()->numberBetween(1),
            'expiration' => fake()->numberBetween(1),
        ];
    }
}
