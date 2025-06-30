<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Queries;

use Enjin\Platform\Models\Indexer\Attribute;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Indexer\CollectionAccount;
use Enjin\Platform\Models\Indexer\Token;
use Enjin\Platform\Models\Indexer\TokenAccount;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Illuminate\Support\Arr;
use Override;

class GetCollectionsTest extends TestCaseGraphQL
{
    protected string $method = 'GetCollections';

    protected Wallet $wallet;
    protected array $collections;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->wallet = Wallet::factory()->create();
        $this->collections = $this->generateCollections();
    }

    #[Override]
    protected function tearDown(): void
    {
        Collection::destroy($this->collections);

        parent::tearDown();
    }

    public function test_it_can_fetch_with_no_args(): void
    {
        $response = $this->graphql($this->method);

        $this->assertNotEmpty($response['edges']);
    }

    public function test_it_can_fetch_with_empty_args(): void
    {
        $response = $this->graphql($this->method, []);

        $this->assertNotEmpty($response['edges']);
    }

    public function test_it_can_fetch_with_null_collection_ids(): void
    {
        $response = $this->graphql($this->method, [
            'ids' => null,
        ]);

        $this->assertNotEmpty($response['edges']);
    }

    public function test_it_can_fetch_with_empty_collection_ids(): void
    {
        $response = $this->graphql($this->method, [
            'ids' => [],
        ]);

        $this->assertNotEmpty($response['edges']);
    }

    public function test_it_can_fetch_with_null_after(): void
    {
        $response = $this->graphql($this->method, [
            'after' => null,
        ]);

        $this->assertNotEmpty($response['edges']);
        $this->assertFalse($response['pageInfo']['hasPreviousPage']);
    }

    public function test_it_can_fetch_with_null_first(): void
    {
        $response = $this->graphql($this->method, [
            'first' => null,
        ]);

        $this->assertNotEmpty($response['edges']);
    }

    public function test_it_can_filter_by_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'ids' => [$collectionId = $this->collections[0]->id],
        ]);

        $this->assertTrue($response['totalCount'] === 1);
        $this->assertEquals(
            $collectionId,
            $response['edges'][0]['node']['id']
        );
    }

    public function test_it_can_get_a_single_collection_with_all_data(): void
    {
        $token = Token::firstWhere('collection_id', '=', ($collection = fake()->randomElement($this->collections))->id);

        $attribute = Attribute::factory([
            'collection_id' => $collection->id,
            'token_id' => null,
        ])->create();

        $collectionAccount = CollectionAccount::firstWhere([
            'collection_id' => $collection->id,
            'account_id' => $this->wallet->id,
        ]);

        $response = $this->graphql($this->method, [
            'ids' => [$collectionId = $collection->id],
        ]);

        $this->assertArrayContainsArray([
            'collectionId' => $collectionId,
            'maxTokenCount' => Arr::get($collection->mint_policy, 'maxTokenCount'),
            'maxTokenSupply' => Arr::get($collection->mint_policy, 'maxTokenSupply'),
            'forceCollapsingSupply' => Arr::get($collection->mint_policy, 'forceSingleMint'),
            'frozen' => Arr::get($collection->transfer_policy, 'isFrozen'),
            'network' => $collection->network,
            'owner' => [
                'account' => [
                    'publicKey' => $this->wallet->id,
                ],
            ],
            'attributes' => [
                0 => [
                    'key' => $attribute->key,
                    'value' => $attribute->value,
                ],
            ],
            'tokens' => [
                'edges' => [
                    [
                        'node' => [
                            'tokenId' => $token->token_id,
                        ],
                    ],
                ],
            ],
            'accounts' => [
                'edges' => [
                    [
                        'node' => [
                            'accountCount' => $collectionAccount->account_count,
                            'isFrozen' => $collectionAccount->is_frozen,
                            'wallet' => [
                                'account' => [
                                    'publicKey' => $this->wallet->id,
                                ],
                            ],
                            'approvals' => [
                                [
                                    'expiration' => null,
                                    'wallet' => [
                                        'account' => [
                                            'publicKey' => $this->wallet->id,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $response['edges'][0]['node']);
    }

    public function test_it_can_get_a_collection_with_big_int_collection_id(): void
    {
        Collection::where('id', '=', $collectionId = Hex::MAX_UINT128)->delete();
        Collection::factory([
            'id' => $collectionId,
        ])->create();

        $response = $this->graphql($this->method, [
            'ids' => [$collectionId],
        ]);

        $this->assertEquals(
            $collectionId,
            $response['edges'][0]['node']['id']
        );
    }

    public function test_it_will_return_empty_for_a_collection_id_that_doesnt_exists(): void
    {
        Collection::where('id', '=', $collectionId = (string) fake()->numberBetween(2000))?->delete();

        $response = $this->graphql($this->method, [
            'ids' => [$this->collections[0]->id, $collectionId],
        ]);

        $this->assertTrue($response['totalCount'] === 1);
    }

    /**
     * Tests for unhappy paths.
     */
    public function test_it_will_fail_with_collection_id_negative(): void
    {
        $response = $this->graphql($this->method, [
            'ids' => ['-1'],
        ], true);

        $this->assertArrayContainsArray([
            'ids.0' => ['The ids.0 is too small, the minimum value it can be is 0.'],
        ], $response['error']);
    }

    public function test_it_will_fail_with_invalid_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'ids' => 'invalid',
        ], true);

        $this->assertArrayContainsArray([
            'ids.0' => ['The ids.0 field must be a number.'],
        ], $response['error']);
    }

    public function test_it_will_fail_with_invalid_collection_ids(): void
    {
        $response = $this->graphql($this->method, [
            'ids' => ['invalid'],
        ], true);

        $this->assertArrayContainsArray([
            'ids.0' => ['The ids.0 field must be a number.'],
        ], $response['error']);
    }

    public function test_it_will_fail_with_overflow_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'ids' => [Hex::MAX_UINT256],
        ], true);

        $this->assertArrayContainsArray([
            'ids.0' => ['The ids.0 is too large, the maximum value it can be is 340282366920938463463374607431768211455.'],
        ], $response['error']);
    }

    protected function generateCollections(?int $numberOfTransactions = 5): array
    {
        return array_map(
            fn () => $this->createCollection(),
            range(0, $numberOfTransactions)
        );
    }

    protected function createCollection(): Collection
    {
        $collection = Collection::factory([
            'owner_id' => $this->wallet,
            'attribute_count' => 1,
        ])->create();

        $token = Token::factory([
            'collection_id' => $collection,
            'attribute_count' => 1,
        ])->create();

        CollectionAccount::factory([
            'collection_id' => $collection,
            'account_id' => $this->wallet,
            'account_count' => 1,
            'approvals' => [['accountId' => $this->wallet->id]],
        ])->create();

        TokenAccount::factory([
            'collection_id' => $collection,
            'token_id' => $token,
            'account_id' => $this->wallet,
        ])->create();

        Attribute::factory([
            'collection_id' => $collection,
        ])->create();

        Attribute::factory([
            'token_id' => $token,
        ])->create();

        return $collection;
    }
}
