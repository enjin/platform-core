<?php

namespace Enjin\Platform\Tests\Packages\Traits;

use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Models\Wallet;
use Illuminate\Database\Eloquent\Model;

trait CreateCollectionData
{
    /**
     * The wallet account.
     */
    protected Model $wallet;

    /**
     * The collection.
     */
    protected Model $collection;

    /**
     * The token.
     */
    protected Model $token;

    /**
     * Create collection data.
     */
    public function createCollectionData(?string $publicKey = null): void
    {
        $this->wallet = Wallet::firstOrCreate(
            ['public_key' => $publicKey ?: config('enjin-platform.chains.daemon-account')],
            [
                'external_id' => fake()->unique()->uuid(),
                'managed' => fake()->boolean(),
                'verification_id' => fake()->unique()->uuid(),
                'network' => 'developer',
                'linking_code' => null,
            ]
        );

        $this->collection = Collection::create([
            'collection_chain_id' => (string) fake()->unique()->numberBetween(2000),
            'owner_wallet_id' => $this->wallet->id,
            'max_token_count' => fake()->numberBetween(1),
            'max_token_supply' => (string) fake()->numberBetween(1),
            'force_collapsing_supply' => fake()->boolean(),
            'is_frozen' => false,
            'token_count' => '0',
            'attribute_count' => '0',
            'total_deposit' => '0',
            'network' => 'developer',
        ]);

        $this->token = Token::create([
            'collection_id' => $this->collection->id,
            'token_chain_id' => (string) fake()->unique()->numberBetween(2000),
            'supply' => (string) fake()->numberBetween(1),
            'cap' => null,
            'cap_supply' => null,
            'is_frozen' => false,
            'attribute_count' => '0',
        ]);
    }
}
