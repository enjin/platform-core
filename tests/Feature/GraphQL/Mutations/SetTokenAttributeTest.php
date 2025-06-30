<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\SetTokenAttributeMutation;
use Enjin\Platform\Models\Indexer\Account;
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

class SetTokenAttributeTest extends TestCaseGraphQL
{
    use MocksHttpClient;

    protected string $method = 'SetTokenAttribute';
    protected Codec $codec;

    protected string $defaultAccount;
    protected Collection $collection;
    protected Token $token;
    protected Encoder $tokenIdEncoder;
    protected Account $wallet;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new Codec();
        $this->wallet = Address::daemon();
        $this->collection = Collection::factory(['owner_id' => $this->wallet])->create();
        $this->token = Token::factory([
            'collection_id' => $collectionId = $this->collection->id,
            'token_id' => $tokenId = fake()->numberBetween(),
            'id' => "{$collectionId}-{$tokenId}",
        ])->create();
        $this->tokenIdEncoder = new Integer($tokenId);
    }

    // Happy Path
    public function test_it_can_skip_validation(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'key' => $key = fake()->word(),
            'value' => $value = fake()->realText(),
            'simulate' => null,
        ]);

        $encodedData = TransactionSerializer::encode('SetAttribute', SetTokenAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode(),
            key: $key,
            value: $value
        ));

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_simulate(): void
    {
        $this->mockFee($feeDetails = app(Generator::class)->fee_details());
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'key' => $key = fake()->word(),
            'value' => $value = fake()->realText(),
            'simulate' => true,
        ]);

        $encodedData = TransactionSerializer::encode('SetAttribute', SetTokenAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode(),
            key: $key,
            value: $value
        ));

        $this->assertIsNumeric($response['deposit']);
        $this->assertArrayContainsArray([
            'id' => null,
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'fee' => $feeDetails['fakeSum'],
            'wallet' => null,
        ], $response);

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_can_create_an_attribute_using_adapter(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'key' => $key = fake()->word(),
            'value' => $value = fake()->realText(),
        ]);

        $encodedData = TransactionSerializer::encode('SetAttribute', SetTokenAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode(),
            key: $key,
            value: $value
        ));

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);
    }

    public function test_it_can_bypass_ownership(): void
    {
        $signingWallet = Account::factory()->create();
        $collection = Collection::factory()->create(['owner_id' => $signingWallet]);
        $token = Token::factory([
            'collection_id' => $collection,
        ])->create();

        $response = $this->graphql($this->method, $params = [
            'collectionId' => $collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_id),
            'key' => fake()->word(),
            'value' => fake()->realText(),
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

    public function test_it_can_create_an_attribute(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'key' => $key = fake()->word(),
            'value' => $value = fake()->realText(),
            'nonce' => $nonce = fake()->numberBetween(),
        ]);

        $encodedData = TransactionSerializer::encode('SetAttribute', SetTokenAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode(),
            key: $key,
            value: $value
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

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_create_an_attribute_with_signing_account(): void
    {
        $signingWallet = Account::factory([
            'id' => $signingAccount = app(Generator::class)->public_key(),
        ])->create();

        $collection = Collection::factory()->create(['owner_id' => $signingWallet]);

        $token = Token::factory([
            'collection_id' => $collection,
        ])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_id),
            'key' => $key = fake()->word(),
            'value' => $value = fake()->realText(),
            'signingAccount' => SS58Address::encode($signingAccount),
        ]);

        $encodedData = TransactionSerializer::encode('SetAttribute', SetTokenAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode($token->token_id),
            key: $key,
            value: $value
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

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_create_an_attribute_with_public_key_signing_account(): void
    {
        $signingWallet = Account::factory([
            'id' => $signingAccount = app(Generator::class)->public_key(),
        ])->create();

        $collection = Collection::factory()->create(['owner_id' => $signingWallet]);

        $token = Token::factory([
            'collection_id' => $collection,
        ])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_id),
            'key' => $key = fake()->word(),
            'value' => $value = fake()->realText(),
            'signingAccount' => $signingAccount,
        ]);

        $encodedData = TransactionSerializer::encode('SetAttribute', SetTokenAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode($token->token_id),
            key: $key,
            value: $value
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

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_create_an_attribute_with_bigint_collection_id(): void
    {
        $this->deleteAllFrom($collectionId = Hex::MAX_UINT128);

        $collection = Collection::factory([
            'id' => $collectionId,
            'owner_id' => $this->wallet,
        ])->create();

        $token = Token::factory([
            'collection_id' => $collection,
        ])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_id),
            'key' => $key = fake()->word(),
            'value' => $value = fake()->realText(),
        ]);

        $encodedData = TransactionSerializer::encode('SetAttribute', SetTokenAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode($token->token_id),
            key: $key,
            value: $value
        ));

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_create_an_attribute_with_bigint_token_id(): void
    {
        $collection = Collection::factory([
            'owner_id' => $this->wallet,
        ])->create();

        Token::factory([
            'collection_id' => $collectionId = $collection->id,
            'token_id' => $tokenId = Hex::MAX_UINT128,
            'id' => "{$collectionId}-{$tokenId}",
        ])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
            'key' => $key = fake()->word(),
            'value' => $value = fake()->realText(),
        ]);

        $encodedData = TransactionSerializer::encode('SetAttribute', SetTokenAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode($tokenId),
            key: $key,
            value: $value
        ));

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        Event::assertDispatched(TransactionCreated::class);
    }

    // Exception Path
    public function test_it_will_fail_with_invalid_key_length(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'key' => fake()->numerify(str_repeat('#', 257)),
            'value' => fake()->realText(),
        ], true);

        $this->assertArrayContainsArray(
            ['key' =>  ['The key field is too large.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_value_length(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'key' => fake()->word(),
            'value' => fake()->asciify(str_repeat('*', 1025)),
        ], true);

        $this->assertArrayContainsArray(
            ['value' =>  ['The value field is too large.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_for_collection_that_doesnt_exists(): void
    {
        Collection::where('id', '=', $collectionId = fake()->numberBetween(2000))?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'key' => fake()->word(),
            'value' => fake()->realText(),
        ], true);

        $this->assertArrayContainsArray(
            ['collectionId' => ['The selected collection id is invalid.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_invalid_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => 'not_valid',
            'tokenId' => $this->token->token_id,
            'key' => fake()->word(),
            'value' => fake()->realText(),
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value "not_valid"; Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_no_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'tokenId' => $this->token->token_id,
            'key' => fake()->word(),
            'value' => fake()->realText(),
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
            'tokenId' => $this->token->token_id,
            'key' => fake()->word(),
            'value' => fake()->realText(),
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of non-null type "BigInt!" must not be null.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_for_token_that_doesnt_exists(): void
    {
        Token::where('token_id', '=', $tokenId = fake()->numberBetween())?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
            'key' => fake()->word(),
            'value' => fake()->realText(),
        ], true);

        $this->assertArrayContainsArray(
            ['tokenId' => ['The token id does not exist in the specified collection.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_invalid_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'tokenId' => ['integer' => 'not_valid'],
            'key' => fake()->word(),
            'value' => fake()->realText(),
        ], true);

        $this->assertStringContainsString(
            'Variable "$tokenId" got invalid value "not_valid" at "tokenId.integer"; Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_no_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'key' => fake()->word(),
            'value' => fake()->realText(),
        ], true);

        $this->assertEquals(
            'Variable "$tokenId" of required type "EncodableTokenIdInput!" was not provided.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_null_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'tokenId' => ['integer' => null],
            'key' => fake()->word(),
            'value' => fake()->realText(),
        ], true);

        $this->assertEquals(
            'The integer field must have a value.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_no_key(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'value' => fake()->realText(),
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
            'value' => fake()->realText(),
        ], true);

        $this->assertStringContainsString(
            'Variable "$key" of non-null type "String!" must not be null.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_no_value(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'key' => fake()->word,
        ], true);

        $this->assertStringContainsString(
            'Variable "$value" of required type "String!" was not provided.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_null_value(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'key' => fake()->word,
            'value' => null,
        ], true);

        $this->assertStringContainsString(
            'Variable "$value" of non-null type "String!" must not be null.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
