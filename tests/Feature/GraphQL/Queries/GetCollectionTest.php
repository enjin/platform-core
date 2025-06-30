<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Queries;

use Enjin\Platform\Models\Indexer\Account;
use Enjin\Platform\Models\Indexer\Attribute;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Indexer\CollectionAccount;
use Enjin\Platform\Models\Indexer\Token;
use Enjin\Platform\Models\Indexer\TokenAccount;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Illuminate\Support\Arr;
use Override;

class GetCollectionTest extends TestCaseGraphQL
{
    protected string $method = 'GetCollection';

    protected Account $wallet;
    protected Account $collectionOwner;
    protected Collection $collection;
    protected Token $token;
    protected CollectionAccount $collectionAccount;
    protected TokenAccount $tokenAccount;
    protected Attribute $collectionAttribute;
    protected Attribute $tokenAttribute;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->wallet = Account::factory()->create();
        $this->collectionOwner = Account::factory()->create();
        $this->collection = Collection::factory([
            'owner_id' => $this->collectionOwner,
            'attribute_count' => 1,
        ])->create();
        $this->token = Token::factory([
            'collection_id' => $this->collection,
            'attribute_count' => 1,
        ])->create();
        $this->collectionAccount = CollectionAccount::factory([
            'collection_id' => $this->collection,
            'account_id' => $this->wallet,
            'account_count' => 1,
            'approvals' => [['accountId' => $this->collectionOwner->id]],
        ])->create();
        $this->tokenAccount = TokenAccount::factory([
            'collection_id' => $this->collection,
            'token_id' => $this->token,
            'account_id' => $this->wallet,
        ])->create();
        $this->collectionAttribute = Attribute::factory([
            'collection_id' => $this->collection,
            'token_id' => null,
        ])->create();
        $this->tokenAttribute = Attribute::factory([
            'collection_id' => $this->collection,
            'token_id' => $this->token,
        ])->create();
    }

    public function test_it_can_get_a_collection_with_all_data(): void
    {
        $response = $this->graphql($this->method, [
            'id' => $collectionId = $this->collection->id,
        ]);

        $this->assertArrayContainsArray([
            'collectionId' => $collectionId,
            'maxTokenCount' => Arr::get($this->collection->mint_policy, 'maxTokenCount'),
            'maxTokenSupply' => Arr::get($this->collection->mint_policy, 'maxTokenSupply'),
            'forceCollapsingSupply' => Arr::get($this->collection->mint_policy, 'forceSingleMint'),
            'frozen' => Arr::get($this->collection->transfer_policy, 'isFrozen'),
            'network' => $this->collection->network,
            'owner' => [
                'account' => [
                    'publicKey' => $this->collectionOwner->id,
                ],
            ],
            'attributes' => [
                [
                    'key' => $this->collectionAttribute->key,
                    'value' => $this->collectionAttribute->value,
                ],
            ],
            'tokens' => [
                'edges' => [
                    [
                        'node' => [
                            'tokenId' => $this->token->token_id,
                        ],
                    ],
                ],
            ],
            'accounts' => [
                'edges' => [
                    [
                        'node' => [
                            'accountCount' => $this->collectionAccount->account_count,
                            'isFrozen' => $this->collectionAccount->is_frozen,
                            'wallet' => [
                                'account' => [
                                    'publicKey' => $this->wallet->id,
                                ],
                            ],
                            'approvals' => [
                                [
                                    'expiration' => Arr::get($this->collectionAccount->approvals, '0.expiration'),
                                    'wallet' => [
                                        'account' => [
                                            'publicKey' => $this->collectionOwner->id,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $response);
    }

    public function test_it_can_get_a_collection_with_big_int_collection_id(): void
    {
        Token::where('collection_id', Hex::MAX_UINT128)?->delete();
        Collection::find(Hex::MAX_UINT128)?->delete();

        $collection = Collection::factory([
            'id' => Hex::MAX_UINT128,
        ])->create();

        $response = $this->graphql($this->method, [
            'id' => $collectionId = $collection->id,
        ]);

        $this->assertArrayContainsArray([
            'id' => $collectionId,
        ], $response);
    }

    public function test_it_max_token_count_can_be_null(): void
    {
        $collection = Collection::factory([
            'mint_policy' => [
                'maxTokenSupply' => (string) fake()->randomNumber(),
                'forceSingleMint' => fake()->boolean(),
            ],
        ])->create();

        $response = $this->graphql($this->method, [
            'id' => $collection->id,
        ]);

        $this->assertArrayContainsArray([
            'id' => $collection->id,
            'maxTokenCount' => null,
        ], $response);
    }

    public function test_it_max_token_supply_can_be_null(): void
    {
        $collection = Collection::factory([
            'mint_policy' => [
                'maxTokenCount' => (string) fake()->randomNumber(),
                'forceSingleMint' => fake()->boolean(),
            ],
        ])->create();

        $response = $this->graphql($this->method, [
            'id' => $collection->id,
        ]);

        $this->assertArrayContainsArray([
            'id' => $collection->id,
            'maxTokenSupply' => null,
        ], $response);
    }

    public function test_it_returns_null_for_non_existing_collection(): void
    {
        Collection::where('id', '=', $collectionId = (string) fake()->numberBetween(2000))?->delete();

        $response = $this->graphql($this->method, [
            'id' => $collectionId,
        ]);

        $this->assertNull($response);
    }

    /**
     * Tests for unhappy paths.
     */
    public function test_it_will_fail_with_no_args(): void
    {
        $response = $this->graphql($this->method, [], true);

        $this->assertArrayContainsArray([
            'id' => ['The id field is required when collection id is not present.'],
            'collectionId' => ['The collection id field is required when id is not present.'],
        ], $response['error']);
    }

    public function test_it_will_fail_with_collection_id_null(): void
    {
        $response = $this->graphql($this->method, [
            'id' => null,
        ], true);

        $this->assertArrayContainsArray([
            'id' => ['The id field is required when collection id is not present.'],
            'collectionId' => ['The collection id field is required when id is not present.'],
        ], $response['error']);
    }

    public function test_it_will_fail_with_collection_id_negative(): void
    {
        $response = $this->graphql($this->method, [
            'id' => '-1',
        ], true);

        $this->assertArrayContainsArray([
            'id' => ['The id is too small, the minimum value it can be is 0.'],
        ], $response['error']);
    }

    public function test_it_will_fail_with_collection_id_empty_string(): void
    {
        $response = $this->graphql($this->method, [
            'id' => '',
        ], true);

        $this->assertArrayContainsArray([
            'id' => ['The id field is required when collection id is not present.'],
            'collectionId' => ['The collection id field is required when id is not present.'],
        ], $response['error']);
    }

    public function test_it_will_fail_with_invalid_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'id' => 'invalid',
        ], true);

        $this->assertArrayContainsArray([
            'id' => ['The id field must be a number.'],
        ], $response['error']);
    }

    public function test_it_will_fail_with_overflow_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'id' => Hex::MAX_UINT256,
        ], true);

        $this->assertArrayContainsArray([
            'id' => ['The id is too large, the maximum value it can be is 340282366920938463463374607431768211455.'],
        ], $response['error']);
    }
}
