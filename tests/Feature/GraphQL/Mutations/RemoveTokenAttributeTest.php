<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\RemoveTokenAttributeMutation;
use Enjin\Platform\Models\Attribute;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Rules\IsCollectionOwner;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Token\Encoder;
use Enjin\Platform\Services\Token\Encoders\Integer;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Support\MocksWebsocketClient;
use Facades\Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

class RemoveTokenAttributeTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;
    use MocksWebsocketClient;

    protected string $method = 'RemoveTokenAttribute';
    protected Codec $codec;
    protected Model $collection;
    protected Model $token;
    protected Encoder $tokenIdEncoder;
    protected Model $attribute;
    protected Model $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new Codec();
        $this->wallet = Account::daemon();
        $this->collection = Collection::factory()->create(['owner_wallet_id' => $this->wallet]);
        $this->token = Token::factory(['collection_id' => $this->collection])->create();
        $this->attribute = Attribute::factory()->create([
            'collection_id' => $this->collection,
            'token_id' => $this->token,
        ]);
        $this->tokenIdEncoder = new Integer($this->token->token_chain_id);
    }

    // Happy Path

    public function test_it_can_skip_validation(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = random_int(2000, 3000),
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'key' => $key = $this->attribute->key,
            'skipValidation' => true,
        ]);

        $encodedData = TransactionSerializer::encode('RemoveAttribute', RemoveTokenAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode(),
            key: $key,
        ));

        $this->assertArraySubset([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_simulate(): void
    {
        $this->mockFee($feeDetails = app(Generator::class)->fee_details());
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'key' => $key = Hex::safeConvertToString($this->attribute->key),
            'simulate' => true,
        ]);

        $encodedData = TransactionSerializer::encode('RemoveAttribute', RemoveTokenAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode(),
            key: $key,
        ));

        $this->assertArraySubset([
            'id' => null,
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'fee' => $feeDetails['fakeSum'],
            'deposit' => null,
            'wallet' => null,
        ], $response);

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_can_bypass_ownership(): void
    {
        $signingWallet = Wallet::factory()->create();
        $collection = Collection::factory()->create(['owner_wallet_id' => $signingWallet]);
        $token = Token::factory([
            'collection_id' => $collection,
        ])->create();
        $attribute = Attribute::factory([
            'collection_id' => $collection,
            'token_id' => $token,
        ])->create();

        $response = $this->graphql($this->method, $params = [
            'collectionId' => $collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_chain_id),
            'key' => Hex::safeConvertToString($attribute->key),
            'nonce' => fake()->numberBetween(),
        ], true);
        $this->assertEquals(
            ['collectionId' => ['The collection id provided is not owned by you.']],
            $response['error']
        );

        IsCollectionOwner::bypass();
        $response = $this->graphql($this->method, $params);
        $this->assertNotEmpty($response);
        IsCollectionOwner::unBypass();
    }

    public function test_it_can_remove_an_attribute(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'key' => $key = Hex::safeConvertToString($this->attribute->key),
            'nonce' => $nonce = fake()->numberBetween(),
        ]);

        $encodedData = TransactionSerializer::encode('RemoveAttribute', RemoveTokenAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode(),
            key: $key,
        ));

        $this->assertArraySubset([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'signingPayload' => Substrate::getSigningPayload($encodedData, [
                'nonce' => $nonce,
                'tip' => '0',
            ]),
            'wallet' => null,
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_remove_an_attribute_with_ss58_signing_account(): void
    {
        $signingWallet = Wallet::factory([
            'public_key' => $signingAccount = app(Generator::class)->public_key(),
        ])->create();
        $collection = Collection::factory()->create(['owner_wallet_id' => $signingWallet]);
        $token = Token::factory([
            'collection_id' => $collection,
        ])->create();
        $attribute = Attribute::factory([
            'collection_id' => $collection,
            'token_id' => $token,
        ])->create();
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_chain_id),
            'key' => $key = Hex::safeConvertToString($attribute->key),
            'signingAccount' => SS58Address::encode($signingAccount),
        ]);

        $encodedData = TransactionSerializer::encode('RemoveAttribute', RemoveTokenAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
            key: $key,
        ));

        $this->assertArraySubset([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => [
                'account' => [
                    'publicKey' => $signingAccount,
                ],
            ],
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_remove_an_attribute_with_public_key_signing_account(): void
    {
        $signingWallet = Wallet::factory([
            'public_key' => $signingAccount = app(Generator::class)->public_key(),
        ])->create();
        $collection = Collection::factory()->create(['owner_wallet_id' => $signingWallet]);
        $token = Token::factory([
            'collection_id' => $collection,
        ])->create();
        $attribute = Attribute::factory([
            'collection_id' => $collection,
            'token_id' => $token,
        ])->create();
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_chain_id),
            'key' => $key = Hex::safeConvertToString($attribute->key),
            'signingAccount' => $signingAccount,
        ]);

        $encodedData = TransactionSerializer::encode('RemoveAttribute', RemoveTokenAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
            key: $key,
        ));

        $this->assertArraySubset([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => [
                'account' => [
                    'publicKey' => $signingAccount,
                ],
            ],
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_remove_an_attribute_with_bigint_collection_id(): void
    {
        Collection::where('collection_chain_id', Hex::MAX_UINT128)->update(['collection_chain_id' => random_int(1, 1000)]);
        $collection = Collection::factory([
            'collection_chain_id' => Hex::MAX_UINT128,
            'owner_wallet_id' => $this->wallet,
        ])->create();
        $collectionId = $collection->collection_chain_id;

        $token = Token::factory([
            'collection_id' => $collection,
        ])->create();

        $attribute = Attribute::factory([
            'collection_id' => $collection,
            'token_id' => $token,
        ])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_chain_id),
            'key' => $key = Hex::safeConvertToString($attribute->key),
        ]);

        $encodedData = TransactionSerializer::encode('RemoveAttribute', RemoveTokenAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
            key: $key,
        ));

        $this->assertArraySubset([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_remove_an_attribute_with_bigint_token_id(): void
    {
        $collection = Collection::factory([
            'collection_chain_id' => $collectionId = fake()->numberBetween(2000),
            'owner_wallet_id' => $this->wallet,
        ])->create();

        Token::where('token_chain_id', Hex::MAX_UINT128)->update(['token_chain_id' => random_int(1, 1000)]);
        $token = Token::factory([
            'collection_id' => $collection,
            'token_chain_id' => $tokenId = Hex::MAX_UINT128,
        ])->create();

        $attribute = Attribute::factory([
            'collection_id' => $collection,
            'token_id' => $token,
        ])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
            'key' => $key = Hex::safeConvertToString($attribute->key),
        ]);

        $encodedData = TransactionSerializer::encode('RemoveAttribute', RemoveTokenAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode($tokenId),
            key: $key,
        ));

        $this->assertArraySubset([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    // Exception Path

    public function test_it_fail_with_for_collection_that_doesnt_exists(): void
    {
        Collection::where('collection_chain_id', '=', $collectionId = fake()->numberBetween(2000))?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($this->token->token_chain_id),
            'key' => $this->attribute->key,
        ], true);

        $this->assertArraySubset(
            ['collectionId' => ['The selected collection id is invalid.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_for_token_that_doesnt_exists(): void
    {
        Token::where('token_chain_id', '=', $tokenId = fake()->numberBetween())?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
            'key' => $this->attribute->key,
        ], true);

        $this->assertArraySubset(
            ['tokenId' => ['The token id doesn\'t exist.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_invalid_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => 'not_valid',
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'key' => $this->attribute->key,
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value "not_valid"; Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_invalid_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => 'not_valid',
            'key' => $this->attribute->key,
        ], true);

        $this->assertStringContainsString(
            'Variable "$tokenId" got invalid value "not_valid"; Expected type "EncodableTokenIdInput" to be an object',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_no_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'key' => $this->attribute->key,
        ], true);

        $this->assertEquals(
            'Variable "$collectionId" of required type "BigInt!" was not provided.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_no_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'key' => $this->attribute->key,
        ], true);

        $this->assertArraySubset(
            ['message' => 'Variable "$tokenId" of required type "EncodableTokenIdInput!" was not provided.'],
            $response['errors'][0]
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_null_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => null,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'key' => $this->attribute->key,
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of non-null type "BigInt!" must not be null.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_null_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => null,
            'key' => $this->attribute->key,
        ], true);

        $this->assertArraySubset(
            ['message' => 'Variable "$tokenId" of non-null type "EncodableTokenIdInput!" must not be null.'],
            $response['errors'][0]
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_no_key(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
        ], true);

        $this->assertStringContainsString(
            'Variable "$key" of required type "String!" was not provided.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_null_key(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'key' => null,
        ], true);

        $this->assertStringContainsString(
            'Variable "$key" of non-null type "String!" must not be null.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_empty_key(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'key' => '',
        ], true);

        $this->assertArraySubset(
            ['key' => ['The key field must have a value.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_key_doesnt_exists(): void
    {
        Attribute::where('key', '=', $key = fake()->word())?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'key' => $key,
        ], true);

        $this->assertArraySubset(
            ['key' => ['The key does not exist in the specified token.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
