<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\RemoveAllAttributesMutation;
use Enjin\Platform\Models\Indexer\Attribute;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Indexer\Token;
use Enjin\Platform\Rules\IsCollectionOwner;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Token\Encoder;
use Enjin\Platform\Services\Token\Encoders\Integer;
use Enjin\Platform\Support\Address;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Support\MocksHttpClient;
use Facades\Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Faker\Generator;
use Illuminate\Support\Facades\Event;
use Override;

class RemoveAllAttributesTest extends TestCaseGraphQL
{
    use MocksHttpClient;

    protected string $method = 'RemoveAllAttributes';
    protected Codec $codec;
    protected Collection $collection;
    protected Token $token;
    protected Encoder $tokenIdEncoder;
    protected Attribute $attribute;
    protected Address $wallet;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->codec = new Codec();
        $this->wallet = Address::daemon();
        $this->collection = Collection::factory()->create(['owner_id' => $this->wallet]);
        $this->token = Token::factory(['collection_id' => $this->collection])->create();
        $this->attribute = Attribute::factory()->create([
            'collection_id' => $this->collection,
            'token_id' => $this->token,
        ]);
        $this->tokenIdEncoder = new Integer($this->token->token_chain_id);
    }

    public function test_it_can_skip_validation(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = random_int(1, 1000),
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'attributeCount' => $attributeCount = 1,
            'skipValidation' => true,
        ]);

        $encodedData = TransactionSerializer::encode($this->method, RemoveAllAttributesMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode(),
            attributeCount: $attributeCount,
        ));

        $this->assertArrayContainsArray([
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
            'attributeCount' => $attributeCount = 1,
            'simulate' => true,
        ]);

        $encodedData = TransactionSerializer::encode($this->method, RemoveAllAttributesMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode(),
            attributeCount: $attributeCount,
        ));

        $this->assertArrayContainsArray([
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
        $signingWallet = Address::factory()->create();
        $collection = Collection::factory()->create(['owner_id' => $signingWallet]);
        $token = Token::factory([
            'collection_id' => $collection,
        ])->create();
        $response = $this->graphql($this->method, $params = [
            'collectionId' => $collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_chain_id),
            'attributeCount' => 1,
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
            'attributeCount' => $attributeCount = 1,
            'nonce' => $nonce = fake()->numberBetween(),
        ]);

        $encodedData = TransactionSerializer::encode($this->method, RemoveAllAttributesMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode(),
            attributeCount: $attributeCount,
        ));

        $this->assertArrayContainsArray([
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
        $wallet = Address::factory([
            'public_key' => $signingAccount = app(Generator::class)->public_key,
        ])->create();
        $collection = Collection::factory([
            'owner_id' => $wallet,
            'collection_chain_id' => $collectionId = fake()->numberBetween(2000),
        ])->create();
        $token = Token::factory([
            'collection_id' => $collection,
            'token_chain_id' => $tokenId = fake()->numberBetween(),
        ])->create();
        Attribute::factory([
            'collection_id' => $collection,
            'token_id' => $token,
        ])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
            'attributeCount' => $attributeCount = 1,
            'signingAccount' => SS58Address::encode($signingAccount),
        ]);

        $encodedData = TransactionSerializer::encode($this->method, RemoveAllAttributesMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode($tokenId),
            attributeCount: $attributeCount,
        ));

        $this->assertArrayContainsArray([
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
        $wallet = Address::factory([
            'public_key' => $signingAccount = app(Generator::class)->public_key,
        ])->create();
        $collection = Collection::factory([
            'owner_id' => $wallet,
            'collection_chain_id' => $collectionId = fake()->numberBetween(2000),
        ])->create();
        $token = Token::factory([
            'collection_id' => $collection,
            'token_chain_id' => $tokenId = fake()->numberBetween(),
        ])->create();
        Attribute::factory([
            'collection_id' => $collection,
            'token_id' => $token,
        ])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
            'attributeCount' => $attributeCount = 1,
            'signingAccount' => $signingAccount,
        ]);

        $encodedData = TransactionSerializer::encode($this->method, RemoveAllAttributesMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode($tokenId),
            attributeCount: $attributeCount,
        ));

        $this->assertArrayContainsArray([
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

    public function test_it_can_remove_an_attribute_with_empty_attribute_count(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'attributeCount' => null,
        ]);

        $encodedData = TransactionSerializer::encode($this->method, RemoveAllAttributesMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode(),
            attributeCount: 1,
        ));

        $this->assertArrayContainsArray([
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

    public function test_it_can_remove_an_attribute_with_no_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $this->collection->collection_chain_id,
            'attributeCount' => 1,
        ]);

        $encodedData = TransactionSerializer::encode($this->method, RemoveAllAttributesMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: null,
            attributeCount: 1,
        ));

        $this->assertArrayContainsArray([
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

    public function test_it_can_remove_an_attribute_with_bigint_collection_id(): void
    {
        Collection::where('collection_chain_id', Hex::MAX_UINT128)->update(['collection_chain_id' => fake()->numberBetween()]);
        $collection = Collection::factory(['collection_chain_id' => Hex::MAX_UINT128, 'owner_id' => $this->wallet->id])->create();
        $collectionId = $collection->collection_chain_id;

        $token = Token::factory(['collection_id' => $collection])->create();
        Attribute::factory(['collection_id' => $collection, 'token_id' => $token])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_chain_id),
            'attributeCount' => $attributeCount = 1,
        ]);

        $encodedData = TransactionSerializer::encode($this->method, RemoveAllAttributesMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
            attributeCount: $attributeCount,
        ));

        $this->assertArrayContainsArray([
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
        $collection = Collection::factory(['owner_id' => $this->wallet->id, 'collection_chain_id' => $collectionId = fake()->numberBetween(2000)])->create();
        Token::where('token_chain_id', Hex::MAX_UINT128)->update(['token_chain_id' => random_int(1, 1000)]);
        $token = Token::factory(['collection_id' => $collection, 'token_chain_id' => $tokenId = Hex::MAX_UINT128])->create();
        Attribute::factory(['collection_id' => $collection, 'token_id' => $token])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_chain_id),
            'attributeCount' => $attributeCount = 1,
        ]);

        $encodedData = TransactionSerializer::encode($this->method, RemoveAllAttributesMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
            attributeCount: $attributeCount,
        ));

        $this->assertArrayContainsArray([
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
        Collection::where('collection_chain_id', '=', $collectionId = '123456')?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'attributeCount' => 1,
        ], true);

        $this->assertArrayContainsArray(
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
            'attributeCount' => 1,
        ], true);

        $this->assertArrayContainsArray(
            ['tokenId' => ['The token id does not exist in the specified collection.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_invalid_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => 'not_valid',
            'tokenId' => $this->token->token_chain_id,
            'attributeCount' => 1,
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
            'attributeCount' => 1,
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
            'tokenId' => $this->token->token_chain_id,
            'attributeCount' => 1,
        ], true);

        $this->assertEquals(
            'Variable "$collectionId" of required type "BigInt!" was not provided.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_null_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => null,
            'tokenId' => $this->token->token_chain_id,
            'attributeCount' => 1,
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of non-null type "BigInt!" must not be null.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
