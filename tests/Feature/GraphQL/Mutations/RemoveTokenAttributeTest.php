<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\RemoveTokenAttributeMutation;
use Enjin\Platform\Models\Indexer\Account;
use Enjin\Platform\Models\Indexer\Attribute;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Indexer\Token;
use Enjin\Platform\Rules\IsCollectionOwner;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Token\Encoder;
use Enjin\Platform\Services\Token\Encoders\Integer;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Support\MocksHttpClient;
use Facades\Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Faker\Generator;
use Illuminate\Support\Facades\Event;
use Override;

class RemoveTokenAttributeTest extends TestCaseGraphQL
{
    use MocksHttpClient;

    protected string $method = 'RemoveTokenAttribute';
    protected Codec $codec;

    protected Collection $collection;
    protected Token $token;
    protected Encoder $tokenIdEncoder;
    protected Attribute $attribute;
    protected Account $wallet;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new Codec();
        $this->wallet = $this->getDaemonAccount();

        $this->collection = Collection::factory([
            'owner_id' => $this->wallet,
        ])->create();

        $this->token = Token::factory([
            'collection_id' => $collectionId = $this->collection->id,
            'token_id' => $tokenId = fake()->numberBetween(),
            'id' => "{$collectionId}-{$tokenId}",
        ])->create();

        $this->attribute = Attribute::factory()->create([
            'collection_id' => $collectionId,
            'token_id' => $this->token->id,
            'key' => $key = fake()->word(),
            'id' => "{$collectionId}-{$tokenId}-" . HexConverter::stringToHexPrefixed($key),
        ]);

        $this->tokenIdEncoder = new Integer($tokenId);
    }

    // Happy Path

    public function test_it_can_skip_validation(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = fake()->numberBetween(),
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'key' => $key = $this->attribute->key,
            'skipValidation' => true,
        ]);

        $encodedData = TransactionSerializer::encode('RemoveAttribute', RemoveTokenAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode(),
            key: $key,
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
            'collectionId' => $collectionId = $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'key' => $key = $this->attribute->key,
            'simulate' => true,
        ]);

        $encodedData = TransactionSerializer::encode('RemoveAttribute', RemoveTokenAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode(),
            key: $key,
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
        $signingWallet = Account::factory()->create();

        $collection = Collection::factory([
            'owner_id' => $ownerId = $signingWallet->id,
        ])->create();

        $token = Token::factory([
            'collection_id' => $collectionId = $collection->id,
            'token_id' => $tokenId = fake()->numberBetween(),
            'id' => "{$collectionId}-{$tokenId}",
        ])->create();

        Attribute::factory([
            'collection_id' => $collectionId,
            'token_id' => $token->id,
            'key' => $key = fake()->word(),
            'id' => "{$collectionId}-{$tokenId}-" . HexConverter::stringToHexPrefixed($key),
        ])->create();

        $response = $this->graphql($this->method, $params = [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
            'key' => $key,
            'nonce' => fake()->numberBetween(),
        ], true);

        $this->assertArrayContainsArray(
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
            'collectionId' => $collectionId = $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'key' => $key = $this->attribute->key,
            'nonce' => $nonce = fake()->numberBetween(),
        ]);

        $encodedData = TransactionSerializer::encode('RemoveAttribute', RemoveTokenAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode(),
            key: $key,
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
        Account::factory([
            'id' => $ownerId = app(Generator::class)->public_key(),
        ])->create();

        $collection = Collection::factory([
            'owner_id' => $ownerId,
        ])->create();

        $token = Token::factory([
            'collection_id' => $collectionId = $collection->id,
            'token_id' => $tokenId = fake()->numberBetween(),
            'id' => "{$collectionId}-{$tokenId}",
        ])->create();

        $attribute = Attribute::factory([
            'collection_id' => $collectionId,
            'token_id' => $token->id,
            'key' => $key = fake()->word(),
            'id' => "{$collectionId}-{$tokenId}-" . HexConverter::stringToHexPrefixed($key),
        ])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
            'key' => $key = $attribute->key,
            'signingAccount' => SS58Address::encode($ownerId),
        ]);

        $encodedData = TransactionSerializer::encode('RemoveAttribute', RemoveTokenAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode($tokenId),
            key: $key,
        ));

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => [
                'account' => [
                    'publicKey' => $ownerId,
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
        Account::factory([
            'id' => $ownerId = app(Generator::class)->public_key(),
        ])->create();

        $collection = Collection::factory([
            'owner_id' => $ownerId,
        ])->create();

        $token = Token::factory([
            'collection_id' => $collectionId = $collection->id,
            'token_id' => $tokenId = fake()->numberBetween(),
            'id' => "{$collectionId}-{$tokenId}",
        ])->create();

        $attribute = Attribute::factory([
            'collection_id' => $collectionId,
            'token_id' => $token->id,
            'key' => $key = fake()->word(),
            'id' => "{$collectionId}-{$tokenId}-" . HexConverter::stringToHexPrefixed($key),
        ])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
            'key' => $key,
            'signingAccount' => $ownerId,
        ]);

        $encodedData = TransactionSerializer::encode('RemoveAttribute', RemoveTokenAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode($tokenId),
            key: $key,
        ));

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => [
                'account' => [
                    'publicKey' => $ownerId,
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
        $this->deleteAllFrom($collectionId = Hex::MAX_UINT128);

        Collection::factory([
            'id' => $collectionId,
            'collection_id' => $collectionId,
            'owner_id' => $this->wallet,
        ])->create();

        $token = Token::factory([
            'collection_id' => $collectionId,
            'token_id' => $tokenId = fake()->numberBetween(),
            'id' => "{$collectionId}-{$tokenId}",
        ])->create();

        Attribute::factory([
            'collection_id' => $collectionId,
            'token_id' => $token->id,
            'key' => $key = fake()->word(),
            'id' => "{$collectionId}-{$tokenId}-" . HexConverter::stringToHexPrefixed($key),
        ])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
            'key' => $key,
        ]);

        $encodedData = TransactionSerializer::encode('RemoveAttribute', RemoveTokenAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode($tokenId),
            key: $key,
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
        $this->deleteAllFrom($collectionId = $this->collection->id, $tokenId = Hex::MAX_UINT128);

        $token = Token::factory([
            'collection_id' => $collectionId,
            'token_id' => $tokenId,
            'id' => "{$collectionId}-{$tokenId}",
        ])->create();

        Attribute::factory([
            'collection_id' => $collectionId,
            'token_id' => $token->id,
            'key' => $key = fake()->word(),
            'id' => "{$collectionId}-{$tokenId}-" . HexConverter::stringToHexPrefixed($key),
        ])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
            'key' => $key,
        ]);

        $encodedData = TransactionSerializer::encode('RemoveAttribute', RemoveTokenAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode($tokenId),
            key: $key,
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
        $this->deleteAllFrom($collectionId = fake()->numberBetween());

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($this->token->token_id),
            'key' => $this->attribute->key,
        ], true);

        $this->assertArrayContainsArray([
            'collectionId' => ['The selected collection id is invalid.'],
        ], $response['error']);

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_for_token_that_doesnt_exists(): void
    {
        $this->deleteAllFrom($collectionId = $this->collection->id, $tokenId = fake()->numberBetween());

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
            'key' => $this->attribute->key,
        ], true);

        $this->assertArrayContainsArray(
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
            'collectionId' => $this->collection->id,
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

        $this->assertStringContainsString(
            'Variable "$collectionId" of required type "BigInt!" was not provided.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_no_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'key' => $this->attribute->key,
        ], true);

        $this->assertArrayContainsArray(
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
            'collectionId' => $this->collection->id,
            'tokenId' => null,
            'key' => $this->attribute->key,
        ], true);

        $this->assertArrayContainsArray(
            ['message' => 'Variable "$tokenId" of non-null type "EncodableTokenIdInput!" must not be null.'],
            $response['errors'][0]
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_no_key(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
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
            'collectionId' => $this->collection->id,
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
            'collectionId' => $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'key' => '',
        ], true);

        $this->assertArrayContainsArray(
            ['key' => ['The key field must have a value.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_key_doesnt_exists(): void
    {
        Attribute::where('key', '=', $key = fake()->word())?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'key' => $key,
        ], true);

        $this->assertArrayContainsArray(
            ['key' => ['The key does not exist in the specified token.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
