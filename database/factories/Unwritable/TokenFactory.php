<?php

namespace Enjin\Platform\Database\Factories\Unwritable;

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
        return [
            'collection_id' => $collection = Collection::factory()->create(),
            'token_id' => (string) $tokenId = fake()->unique()->numberBetween(),

            'id' => $collection->id . "-$tokenId",
            'supply' => (string) fake()->numberBetween(1),
            'is_frozen' => false,
            'freeze_state' => null,
            'cap' => null,
            'behavior' => null,
            'listing_forbidden' => false,
            'attribute_count' => 0,
            'anyone_can_infuse' => false,
            'infusion' => '0',
            'non_fungible' => true,
            'created_at' => now(),
            'updated_at' => now(),
            // Fields from the indexer that we might not need
            'native_metadata' => null,
            'unit_price' => null,
            'minimum_balance' => 1,
            'mint_deposit' => 1,
            'account_deposit_count' => 0,
            'name' => null,
            'metadata' => null,
            'best_listing_id' => null,
            'recent_listing_id' => null,
            'last_sale_id' => null,
        ];
    }
}
