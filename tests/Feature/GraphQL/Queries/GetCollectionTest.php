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
use Illuminate\Database\Eloquent\Model;
use Override;

class GetCollectionTest extends TestCaseGraphQL
{
    protected string $method = 'GetCollection';

    protected Wallet $wallet;
    protected Wallet $collectionOwner;
    protected Collection $collection;
    protected Token $token;
    protected CollectionAccount $collectionAccount;
    protected Model $collectionAccountApproval;
    protected TokenAccount $tokenAccount;
    protected Attribute $collectionAttribute;
    protected Attribute $tokenAttribute;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->wallet = Wallet::factory()->create();
        $this->collectionOwner = Wallet::factory()->create();
        $this->collection = Collection::factory([
            'owner_wallet_id' => $this->collectionOwner,
            'token_count' => 1,
            'attribute_count' => 1,
        ])->create();
        $this->token = Token::factory([
            'collection_id' => $this->collection,
            'attribute_count' => 1,
        ])->create();
        $this->collectionAccount = CollectionAccount::factory([
            'collection_id' => $this->collection,
            'wallet_id' => $this->wallet,
            'account_count' => 1,
        ])->create();
        $this->collectionAccountApproval = CollectionAccountApproval::factory([
            'collection_account_id' => $this->collectionAccount,
            'wallet_id' => $this->collectionOwner,
        ])->create();
        $this->tokenAccount = TokenAccount::factory([
            'collection_id' => $this->collection,
            'token_id' => $this->token,
            'wallet_id' => $this->wallet,
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
            'collectionId' => $collectionId = $this->collection->collection_chain_id,
        ]);

        $this->assertArrayContainsArray([
            'collectionId' => $collectionId,
            'maxTokenCount' => $this->collection->max_token_count,
            'maxTokenSupply' => $this->collection->max_token_supply,
            'forceCollapsingSupply' => $this->collection->force_collapsing_supply,
            'frozen' => $this->collection->is_frozen,
            'network' => $this->collection->network,
            'owner' => [
                'account' => [
                    'publicKey' => $this->collectionOwner->public_key,
                ],
            ],
            'attributes' => [
                [
                    'key' => Hex::safeConvertToString($this->collectionAttribute->key),
                    'value' => Hex::safeConvertToString($this->collectionAttribute->value),
                ],
            ],
            'tokens' => [
                'edges' => [
                    [
                        'node' => [
                            'tokenId' => $this->token->token_chain_id,
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
                                    'publicKey' => $this->wallet->public_key,
                                ],
                            ],
                            'approvals' => [
                                [
                                    'wallet' => [
                                        'account' => [
                                            'publicKey' => $this->collectionOwner->public_key,
                                        ],
                                    ],
                                    'expiration' => $this->collectionAccountApproval->expiration,
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
        Collection::where('collection_chain_id', '=', Hex::MAX_UINT128)?->delete();

        $collection = Collection::factory([
            'collection_chain_id' => Hex::MAX_UINT128,
        ])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $collection->collection_chain_id,
        ]);

        $this->assertArrayContainsArray([
            'collectionId' => $collectionId,
        ], $response);
    }

    public function test_it_max_token_count_can_be_null(): void
    {
        $collection = Collection::factory([
            'max_token_count' => null,
        ])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $collection->collection_chain_id,
        ]);

        $this->assertArrayContainsArray([
            'collectionId' => $collectionId,
            'maxTokenCount' => null,
        ], $response);
    }

    public function test_it_max_token_supply_can_be_null(): void
    {
        $collection = Collection::factory([
            'max_token_supply' => null,
        ])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $collection->collection_chain_id,
        ]);

        $this->assertArrayContainsArray([
            'collectionId' => $collectionId,
            'maxTokenSupply' => null,
        ], $response);
    }

    public function test_it_will_fail_with_no_args(): void
    {
        $response = $this->graphql($this->method, [], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of required type "BigInt!" was not provided',
            $response['error'],
        );
    }

    // Exception Path

    public function test_it_will_fail_with_collection_id_null(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => null,
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of non-null type "BigInt!" must not be null.',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_collection_id_negative(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => -1,
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value -1; Cannot represent following value as uint256',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_collection_id_empty_string(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => '',
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value (empty string)',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_invalid_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => 'invalid',
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value "invalid"; Cannot represent following value as uint256',
            $response['error'],
        );
    }

    public function test_it_will_fail_if_collection_id_doesnt_exist(): void
    {
        Collection::where('collection_chain_id', '=', $collectionId = fake()->numberBetween(2000))?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
        ], true);

        $this->assertArrayContainsArray(
            ['collectionId' => ['The selected collection id is invalid.']],
            $response['error'],
        );
    }

    public function test_it_will_fail_with_overflow_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => Hex::MAX_UINT256,
        ], true);

        $this->assertArrayContainsArray(
            ['collectionId' => ['The selected collection id is invalid.']],
            $response['error'],
        );
    }
}
