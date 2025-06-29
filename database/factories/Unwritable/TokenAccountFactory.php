<?php

namespace Enjin\Platform\Database\Factories\Unwritable;

use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Indexer\Token;
use Enjin\Platform\Models\Indexer\TokenAccount;
use Enjin\Platform\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TokenAccount>
 */
class TokenAccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = TokenAccount::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'account_id' => $account = Wallet::factory()->create(),
            'token_id' => $token = Token::factory()->create(),
            'collection_id' => $token->collection_id,

            'id' => $account->id . '-' . $token->id,
            'total_balance' => $balance = fake()->numberBetween(1),
            'balance' => $balance,
            'reserved_balance' => 0,
            'locked_balance' => 0,
            'named_reserves' => null,
            'locks' => null,
            'approvals' => null,
            'is_frozen' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
