<?php

namespace Enjin\Platform\Database\Factories\Unwritable;

use Enjin\Platform\BlockchainConstant;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Indexer\Token;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Token>
 */
class TokenFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Token::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $supply = gmp_init(fake()->numberBetween(1));
        $depositPerTokenAccount = gmp_init(BlockchainConstant::DEPOSIT_PER_TOKEN_ACCOUNT);
        $ownerDeposit = gmp_mul(gmp_sub($supply, 1), $depositPerTokenAccount);

        return [
            'collection_id' => Collection::factory(),
            'token_chain_id' => (string) fake()->unique()->numberBetween(),
            'supply' => gmp_strval($supply),
            'cap' => null,
            'cap_supply' => null,
            'is_frozen' => false,
            'royalty_wallet_id' => null,
            'royalty_percentage' => null,
            'is_currency' => false,
            'listing_forbidden' => false,
            'requires_deposit' => true,
            'creation_depositor' => null,
            'creation_deposit_amount' => gmp_strval($depositPerTokenAccount),
            'owner_deposit' => gmp_strval($ownerDeposit),
            'total_token_account_deposit' => '0',
            'attribute_count' => 0,
            'account_count' => 0,
            'infusion' => '0',
            'anyone_can_infuse' => false,
            'decimal_count' => 0,
            'name' => null,
            'symbol' => null,
        ];
    }
}
