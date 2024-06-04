<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Queries;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Models\Attribute;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\CollectionAccount;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Models\TokenAccountApproval;
use Enjin\Platform\Models\TokenAccountNamedReserve;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Services\Token\Encoder;
use Enjin\Platform\Services\Token\Encoders\Integer;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class GetTokenTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;

    protected string $method = 'GetToken';
    protected Model $wallet;
    protected Model $collectionOwner;
    protected Model $collection;
    protected Model $token;
    protected Encoder $tokenIdEncoder;
    protected Model $tokenAccount;
    protected Model $tokenAccountApproval;
    protected Model $tokenAccountNamedReserve;
    protected Model $tokenAttribute;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wallet = Wallet::factory()->create();
        $this->collectionOwner = Wallet::factory()->create();
        $this->collection = Collection::factory([
            'owner_wallet_id' => $this->collectionOwner,
            'token_count' => 1,
        ])->create();
        $this->token = Token::factory([
            'collection_id' => $this->collection,
            'attribute_count' => 1,
        ])->create();
        $this->tokenIdEncoder = new Integer($this->token->token_chain_id);
        CollectionAccount::factory([
            'collection_id' => $this->collection,
            'wallet_id' => $this->wallet,
            'account_count' => 1,
        ])->create();
        $this->tokenAccount = TokenAccount::factory([
            'collection_id' => $this->collection,
            'token_id' => $this->token,
            'wallet_id' => $this->wallet,
        ])->create();
        $this->tokenAttribute = Attribute::factory([
            'collection_id' => $this->collection,
            'token_id' => $this->token,
        ])->create();
        $this->tokenAccountApproval = TokenAccountApproval::factory([
            'token_account_id' => $this->tokenAccount,
            'wallet_id' => $this->collectionOwner,
        ])->create();
        $this->tokenAccountNamedReserve = TokenAccountNamedReserve::factory([
            'token_account_id' => $this->tokenAccount,
        ])->create();
    }

    public function test_it_can_replace_id_metadata(): void
    {
        $this->tokenAttribute->forceFill(['key' => 'uri', 'value' => 'https://example.com/{id}'])->save();

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
        ]);

        $this->assertArraySubset([
            'attributes' => [[
                'key' => 'uri',
                'value' => "https://example.com/{$this->collection->collection_chain_id}-{$this->token->token_chain_id}",
            ],
            ],
        ], $response);
    }

    public function test_it_can_get_a_token_with_all_data(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
        ]);

        $this->assertArraySubset([
            'tokenId' => $this->tokenIdEncoder->encode(),
            'supply' => $supply = $this->token->supply,
            'cap' => $this->token->cap,
            'capSupply' => $this->token->cap_supply,
            'isFrozen' => $this->token->is_frozen,
            'minimumBalance' => $this->token->minimum_balance,
            'unitPrice' => $unitPrice = $this->token->unit_price,
            'mintDeposit' => $supply * $unitPrice,
            'attributeCount' => $this->token->attribute_count,
            'collection' => [
                'collectionId' => $collectionId,
            ],
            'attributes' => [
                [
                    'key' => Hex::safeConvertToString($this->tokenAttribute->key),
                    'value' => Hex::safeConvertToString($this->tokenAttribute->value),
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
                                    'publicKey' => $this->wallet->public_key,
                                ],
                            ],
                            'approvals' => [
                                [
                                    'amount' => $this->tokenAccountApproval->amount,
                                    'expiration' => $this->tokenAccountApproval->expiration,
                                    'wallet' => [
                                        'account' => [
                                            'publicKey' => $this->collectionOwner->public_key,
                                        ],
                                    ],
                                ],
                            ],
                            'namedReserves' => [
                                [
                                    'pallet' => $this->tokenAccountNamedReserve->pallet,
                                    'amount' => $this->tokenAccountNamedReserve->amount,
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
            'token_chain_id' => Hex::MAX_UINT128,
        ])->create();
        $collection = Collection::find($token->collection_id);

        $response = $this->graphql($this->method, [
            'collectionId' => $collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_chain_id),
        ]);

        $this->assertArraySubset([
            'tokenId' => $this->tokenIdEncoder->encode($token->token_chain_id),
        ], $response);
    }

    public function test_it_can_fetch_token_metadata(): void
    {
        $collection = Collection::factory()->create();
        $token = Token::factory([
            'collection_id' => $collection,
        ])->create();

        Attribute::factory([
            'collection_id' => $collection,
            'token_id' => $token->id,
            'key' => 'uri',
            'value' => 'https://enjin.io/mock/metadata/token.json',
        ])->create();

        Http::fake(fn () => Http::response([
            'name' => 'Mock Token',
            'description' => 'Mock token description',
            'image' => 'https://enjin.io/mock/metadata/token.png',
        ]));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_chain_id),
            'metadata' => true,
        ]);

        $this->assertArraySubset([
            'tokenId' => $this->tokenIdEncoder->encode($token->token_chain_id),
            'metadata' => (object) [
                'name' => 'Mock Token',
                'description' => 'Mock token description',
                'image' => 'https://enjin.io/mock/metadata/token.png',
            ],
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

    public function test_it_will_fail_with_no_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'tokenId' => $this->token->token_chain_id,
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of required type "BigInt!" was not provided.',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_no_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
        ], true);

        $this->assertStringContainsString(
            'Variable "$tokenId" of required type "EncodableTokenIdInput!" was not provided.',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_collection_id_equals_null(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => null,
            'tokenId' => $this->token->token_chain_id,
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of non-null type "BigInt!" must not be null.',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_token_id_equals_null(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => null,
        ], true);

        $this->assertStringContainsString(
            'Variable "$tokenId" of non-null type "EncodableTokenIdInput!" must not be null.',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_invalid_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => 'invalid',
            'tokenId' => $this->token->token_chain_id,
        ], true);

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

        $this->assertStringContainsString(
            'Variable "$tokenId" got invalid value "invalid"; Expected type "EncodableTokenIdInput" to be an object',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_collection_id_negative(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => -1,
            'tokenId' => $this->token->token_chain_id,
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value -1; Cannot represent following value as uint256',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_token_id_negative(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(-1),
        ], true);

        $this->assertStringContainsString(
            'Variable "$tokenId" got invalid value -1 at "tokenId.integer"; Cannot represent following value as uint256',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_collection_id_that_doesnt_exists(): void
    {
        Collection::where('collection_chain_id', '=', $collectionId = fake()->numberBetween(2000))?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
        ], true);

        $this->assertArraySubset(
            ['collectionId' => ['The selected collection id is invalid.']],
            $response['error'],
        );
    }

    public function test_it_will_fail_with_token_id_that_doesnt_exists(): void
    {
        Token::where('token_chain_id', '=', $tokenId = fake()->numberBetween())?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
        ], true);

        $this->assertArraySubset(
            ['tokenId' => ['The token id doesn\'t exist.']],
            $response['error'],
        );
    }

    public function test_it_will_fail_with_empty_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => '',
            'tokenId' => $this->token->token_chain_id,
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value (empty string); Cannot represent following value as uint256',
            $response['error'],
        );
    }

    public function test_it_will_fail_with_empty_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => '',
        ], true);

        $this->assertStringContainsString(
            'Variable "$tokenId" got invalid value (empty string); Expected type "EncodableTokenIdInput" to be an object',
            $response['error'],
        );
    }
}
