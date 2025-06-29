<?php

namespace Enjin\Platform\Database\Factories\Unwritable;

use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Models\TokenAccount;
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
            'wallet_id' => Wallet::factory(),
            'collection_id' => $collectionFactory = Collection::factory()->create(),
            'token_id' => Token::factory([
                'collection_id' => $collectionFactory,
            ]),
            'balance' => (string) fake()->numberBetween(1),
            'reserved_balance' => '0',
            'is_frozen' => false,
        ];
    }
}
