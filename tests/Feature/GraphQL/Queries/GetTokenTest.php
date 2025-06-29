<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Queries;

use Enjin\Platform\Models\Indexer\Attribute;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Indexer\CollectionAccount;
use Enjin\Platform\Models\Indexer\Token;
use Enjin\Platform\Models\Indexer\TokenAccount;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Services\Token\Encoder;
use Enjin\Platform\Services\Token\Encoders\Integer;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Override;

class GetTokenTest extends TestCaseGraphQL
{
    protected string $method = 'GetToken';

    protected Wallet $wallet;
    protected Wallet $collectionOwner;
    protected Collection $collection;
    protected Token $token;
    protected Encoder $tokenIdEncoder;
    protected TokenAccount $tokenAccount;
    protected Attribute $tokenAttribute;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->wallet = Wallet::factory()->create();
        $this->collectionOwner = Wallet::factory()->create();
        $this->collection = Collection::factory([
            'owner_id' => $this->collectionOwner,
        ])->create();
        $this->token = Token::factory([
            'collection_id' => $this->collection,
            'attribute_count' => 1,
        ])->create();
        $this->tokenIdEncoder = new Integer($this->token->token_id);
        CollectionAccount::factory([
            'collection_id' => $this->collection,
            'account_id' => $this->wallet,
            'account_count' => 1,
        ])->create();
        $this->tokenAccount = TokenAccount::factory([
            'collection_id' => $this->collection,
            'token_id' => $this->token,
            'account_id' => $this->wallet,
        ])->create();
        $this->tokenAttribute = Attribute::factory([
            'collection_id' => $this->collection,
            'token_id' => $this->token,
        ])->create();
//        $this->tokenAccountApproval = TokenAccountApproval::factory([
//            'token_account_id' => $this->tokenAccount,
//            'wallet_id' => $this->collectionOwner,
//        ])->create();
//        $this->tokenAccountNamedReserve = TokenAccountNamedReserve::factory([
//            'token_account_id' => $this->tokenAccount,
//        ])->create();
    }

    public function test_it_can_get_a_token_with_all_data(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
        ]);

        $this->assertArrayContainsArray([
            'tokenId' => $this->tokenIdEncoder->encode(),
            'supply' => $this->token->supply,
            'cap' => $this->token->cap,
            'capSupply' => $this->token->cap_supply,
            'isFrozen' => $this->token->is_frozen,
            'attributeCount' => $this->token->attribute_count,
            'collection' => [
                'collectionId' => $collectionId,
            ],
            'attributes' => [
                [
                    'key' => $this->tokenAttribute->key,
                    'value' => $this->tokenAttribute->value,
                ],
            ],
            'accounts' => [
                'totalCount' => 1,
                'edges' => [
                    [
                        'node' => [
                            'balance' => $this->tokenAccount->balance,
                            'reservedBalance' => $this->tokenAccount->reserved_balance,
                            'isFrozen' => $this->tokenAccount->is_frozen,
                            'wallet' => [
                                'account' => [
                                    'publicKey' => $this->wallet->id,
                                ],
                            ],
                            'approvals' => [
                                [
//                                    'amount' => $this->tokenAccountApproval->amount,
//                                    'expiration' => $this->tokenAccountApproval->expiration,
                                    'wallet' => [
                                        'account' => [
                                            'publicKey' => $this->collectionOwner->id,
                                        ],
                                    ],
                                ],
                            ],
                            'namedReserves' => [
                                [
//                                    'pallet' => $this->tokenAccountNamedReserve->pallet,
//                                    'amount' => $this->tokenAccountNamedReserve->amount,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $response);
    }

    public function test_it_can_get_a_collection_with_big_int_token_id(): void
    {
        $token = Token::factory([
            'token_id' => Hex::MAX_UINT128,
        ])->create();
        $collection = Collection::find($token->collection_id);

        $response = $this->graphql($this->method, [
            'collectionId' => $collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_id),
        ]);

        $this->assertArrayContainsArray([
            'tokenId' => $this->tokenIdEncoder->encode($token->token_id),
        ], $response);
    }

    public function test_it_will_fail_with_no_args(): void
    {
        $response = $this->graphql($this->method, [], true);

        $this->assertArrayContainsArray([
            'tokenId' => $this->tokenIdEncoder->encode($this->token->token_id),
        ], $response);
    }

    /**
     * Tests for unhappy paths
     */
    public function test_it_will_fail_with_no_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'tokenId' => $this->tokenIdEncoder->toEncodable($this->token->token_id),
        ], true);

        $this->assertArrayContainsArray([
            'collectionId' => ['The collection id field is required when id is not present.'],
        ], $response['error']);
    }

    public function test_it_will_fail_with_no_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
        ], true);

        ray($response['error']);

        $this->assertArrayContainsArray([
            'collectionId' => ['The collection id field is required when id is not present.'],
        ], $response['error']);
    }

    public function test_it_will_fail_with_collection_id_equals_null(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => null,
            'tokenId' => $this->token->token_id,
        ], true);

        ray($response['error']);

        $this->assertArrayContainsArray([
            'collectionId' => ['The collection id field is required when id is not present.'],
        ], $response['error']);
    }

    public function test_it_will_fail_with_token_id_equals_null(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'tokenId' => null,
        ], true);

        ray($response['error']);

        $this->assertArrayContainsArray([
            'collectionId' => ['The collection id field is required when id is not present.'],
        ], $response['error']);
    }

    public function test_it_will_fail_with_invalid_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => 'invalid',
            'tokenId' => $this->token->token_id,
        ], true);

        ray($response['error']);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value "invalid"; Cannot represent following value as uint256',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_invalid_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => 'invalid',
        ], true);

        ray($response['error']);

        $this->assertStringContainsString(
            'Variable "$tokenId" got invalid value "invalid"; Expected type "EncodableTokenIdInput" to be an object',
            $response['error'],
        );

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'tokenId' => ['integer' => 0],
        ], true);


        $this->assertStringContainsString(
            'Variable "$tokenId" got invalid value "invalid"; Expected type "EncodableTokenIdInput" to be an object',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_collection_id_negative(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => -1,
            'tokenId' => $this->token->token_id,
        ], true);

        ray($response['error']);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value -1; Cannot represent following value as uint256',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_token_id_negative(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(-1),
        ], true);

        ray($response['error']);

        $this->assertStringContainsString(
            'Variable "$tokenId" got invalid value -1 at "tokenId.integer"; Cannot represent following value as uint256',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_collection_id_that_doesnt_exists(): void
    {
        Collection::where('id', '=', $collectionId = (string) fake()->numberBetween(2000))?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
        ], true);

        ray($response['error']);

        $this->assertArrayContainsArray([
            'collectionId' => ['The selected collection id is invalid.']
        ], $response['error']);
    }

    public function test_it_will_fail_with_token_id_that_doesnt_exists(): void
    {
        Token::where('token_id', '=', $tokenId = fake()->numberBetween())?->delete();

        ray($this->collection->id);
        ray($this->tokenIdEncoder->toEncodable($tokenId));

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
        ], true);

        ray($response['error']);

        $this->assertArrayContainsArray([
            'tokenId' => ['The token id doesn\'t exist.'],
            ], $response['error']);
    }

    public function test_it_will_fail_with_empty_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => '',
            'tokenId' => $this->token->token_id,
        ], true);

        ray($response['error']);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value (empty string); Cannot represent following value as uint256',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_empty_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'tokenId' => '',
        ], true);

        ray($response['error']);

        $this->assertStringContainsString(
            'Variable "$tokenId" got invalid value (empty string); Expected type "EncodableTokenIdInput" to be an object',
            $response['error'],
        );
    }
}
