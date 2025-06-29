<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Queries;

use Enjin\Platform\Models\Attribute;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\CollectionAccount;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as CollectionSupport;
use Illuminate\Support\Facades\Cache;

class GetCollectionsTest extends TestCaseGraphQL
{
    protected string $method = 'GetCollections';
    protected Wallet $wallet;
    protected CollectionSupport $collections;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->wallet = Wallet::factory()->create();
        $this->collections = $this->generateCollections();
    }

    #[\Override]
    protected function tearDown(): void
    {
        Collection::destroy($this->collections);

        parent::tearDown();
    }

    public function test_it_can_fetch_with_no_args(): void
    {
        $response = $this->graphql($this->method);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_get_total_count_correctly(): void
    {
        $collection = Collection::factory()->create();
        Token::factory(random_int(16, 100))->create(['collection_id' => $collection->id]);
        CollectionAccount::factory(random_int(16, 100))->create(['collection_id' => $collection->id]);

        Cache::flush();
        $response = $this->graphql($this->method, [
            'collectionIds' => [$collection->collection_chain_id],
            'tokensLimit' => 1,
            'accountsLimit' => 1,
        ]);
        $this->assertTrue(count($response['edges']) > 0);
        $this->assertCount(1, Arr::get($response, 'edges.0.node.tokens.edges'));
        $this->assertCount(1, Arr::get($response, 'edges.0.node.accounts.edges'));
        $this->assertEquals(
            Token::where('collection_id', $collection->id)->count(),
            Arr::get($response, 'edges.0.node.tokens.totalCount')
        );
        $this->assertEquals(
            CollectionAccount::where('collection_id', $collection->id)->count(),
            Arr::get($response, 'edges.0.node.accounts.totalCount')
        );
    }

    public function test_it_can_fetch_with_empty_args(): void
    {
        $response = $this->graphql($this->method, []);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_fetch_with_null_collection_ids(): void
    {
        $response = $this->graphql($this->method, [
            'collectionIds' => null,
        ]);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_fetch_with_empty_collection_ids(): void
    {
        $response = $this->graphql($this->method, [
            'collectionIds' => [],
        ]);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_fetch_with_null_after(): void
    {
        $response = $this->graphql($this->method, [
            'after' => null,
        ]);

        $this->assertTrue(count($response['edges']) > 0);
        $this->assertFalse($response['pageInfo']['hasPreviousPage']);
    }

    public function test_it_can_fetch_with_null_first(): void
    {
        $response = $this->graphql($this->method, [
            'first' => null,
        ]);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_filter_by_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionIds' => [$collectionId = $this->collections[0]->collection_chain_id],
        ]);

        $this->assertTrue($response['totalCount'] === 1);
        $this->assertEquals(
            $collectionId,
            $response['edges'][0]['node']['collectionId']
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
            'wallet_id' => $this->wallet->id,
        ]);
        $collectionAccountApproval = CollectionAccountApproval::firstWhere([
            'collection_account_id' => $collectionAccount->id,
        ]);

        $response = $this->graphql($this->method, [
            'collectionIds' => [$collectionId = $collection->collection_chain_id],
        ]);

        $this->assertArrayContainsArray([
            'collectionId' => $collectionId,
            'maxTokenCount' => $collection->max_token_count,
            'maxTokenSupply' => $collection->max_token_supply,
            'forceCollapsingSupply' => $collection->force_collapsing_supply,
            'frozen' => $collection->is_frozen,
            'network' => $collection->network,
            'owner' => [
                'account' => [
                    'publicKey' => $this->wallet->public_key,
                ],
            ],
            'attributes' => [
                [
                    'key' => Hex::safeConvertToString($attribute->key),
                    'value' => Hex::safeConvertToString($attribute->value),
                ],
            ],
            'tokens' => [
                'edges' => [
                    [
                        'node' => [
                            'tokenId' => $token->token_chain_id,
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
                                    'publicKey' => $this->wallet->public_key,
                                ],
                            ],
                            'approvals' => [
                                [
                                    'wallet' => [
                                        'account' => [
                                            'publicKey' => $this->wallet->public_key,
                                        ],
                                    ],
                                    'expiration' => $collectionAccountApproval->expiration,
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
        Collection::where('collection_chain_id', '=', $collectionId = Hex::MAX_UINT128)->delete();
        Collection::factory([
            'collection_chain_id' => $collectionId,
        ])->create();

        $response = $this->graphql($this->method, [
            'collectionIds' => [$collectionId],
        ]);

        $this->assertEquals(
            $collectionId,
            $response['edges'][0]['node']['collectionId']
        );
    }

    public function test_it_will_return_empty_for_a_collection_id_that_doesnt_exists(): void
    {
        Collection::where('collection_chain_id', '=', $collectionId = fake()->numberBetween(2000))?->delete();

        $response = $this->graphql($this->method, [
            'collectionIds' => [$this->collections[0]->collection_chain_id, $collectionId],
        ]);

        $this->assertTrue($response['totalCount'] === 1);
    }

    public function test_it_will_fail_with_collection_id_negative(): void
    {
        $response = $this->graphql($this->method, [
            'collectionIds' => [-1],
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionIds" got invalid value -1 at "collectionIds[0]"; Cannot represent following value as uint256',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_invalid_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionIds' => 'invalid',
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionIds" got invalid value "invalid"; Cannot represent following value as uint256',
            $response['error'],
        );
    }

    // Exception Path

    public function test_it_will_fail_with_invalid_collection_ids(): void
    {
        $response = $this->graphql($this->method, [
            'collectionIds' => ['invalid'],
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionIds" got invalid value "invalid" at "collectionIds[0]"; Cannot represent following value as uint256',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_overflow_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionIds' => [Hex::MAX_UINT256],
        ], true);

        $this->assertArrayContainsArray(
            [
                'collectionIds.0' => [
                    0 => 'The collectionIds.0 is too large, the maximum value it can be is 340282366920938463463374607431768211455.',
                ],
            ],
            $response['error'],
        );
    }

    protected function generateCollections(?int $numberOfTransactions = 5): CollectionSupport
    {
        return collect(range(0, $numberOfTransactions))->map(
            fn () => $this->createCollection(),
        );
    }

    protected function createCollection(): Collection
    {
        $collection = Collection::factory([
            'owner_wallet_id' => $this->wallet,
            'token_count' => 1,
            'attribute_count' => 1,
        ])->create();

        $token = Token::factory([
            'collection_id' => $collection,
            'attribute_count' => 1,
        ])->create();

        $collectionAccount = CollectionAccount::factory([
            'collection_id' => $collection,
            'wallet_id' => $this->wallet,
            'account_count' => 1,
        ])->create();

        CollectionAccountApproval::factory([
            'collection_account_id' => $collectionAccount,
            'wallet_id' => $this->wallet,
        ])->create();

        TokenAccount::factory([
            'collection_id' => $collection,
            'token_id' => $token,
            'wallet_id' => $this->wallet,
        ])->create();

        Attribute::factory([
            'collection_id' => $collection,
        ])->create();

        Attribute::factory([
            'collection_id' => $collection,
            'token_id' => $token,
        ])->create();

        return $collection;
    }
}
