<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\RemoveCollectionAttributeMutation;
use Enjin\Platform\Models\Indexer\Account;
use Enjin\Platform\Models\Indexer\Attribute;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Rules\IsCollectionOwner;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Support\MocksHttpClient;
use Facades\Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Faker\Generator;
use Illuminate\Support\Facades\Event;
use Override;

class RemoveCollectionAttributeTest extends TestCaseGraphQL
{
    use MocksHttpClient;

    protected string $method = 'RemoveCollectionAttribute';
    protected Codec $codec;

    protected Collection $collection;
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

        $this->attribute = Attribute::factory([
            'collection_id' => $collectionId = $this->collection->id,
            'token_id' => null,
            'key' => $key = fake()->word(),
            'id' => "{$collectionId}-" . HexConverter::stringToHexPrefixed($key),
        ])->create();
    }

    // Happy Path

    public function test_it_can_skip_validation(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = fake()->numberBetween(),
            'key' => $key = $this->attribute->key,
            'skipValidation' => true,
        ]);

        $encodedData = TransactionSerializer::encode('RemoveAttribute', RemoveCollectionAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: null,
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
            'key' => $key = $this->attribute->key,
            'simulate' => true,
        ]);

        $encodedData = TransactionSerializer::encode('RemoveAttribute', RemoveCollectionAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: null,
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
        Account::factory([
            'id' => $ownerId = app(Generator::class)->public_key(),
        ])->create();

        $collection = Collection::factory([
            'owner_id' => $ownerId,
        ])->create();

        Attribute::factory([
            'collection_id' => $collectionId = $collection->id,
            'token_id' => null,
            'key' => $key = fake()->word(),
            'id' => "{$collectionId}-" . HexConverter::stringToHexPrefixed($key),
        ])->create();

        $response = $this->graphql($this->method, $params = [
            'collectionId' => $collectionId,
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
            'key' => $key = $this->attribute->key,
            'nonce' => $nonce = fake()->numberBetween(),
        ]);

        $encodedData = TransactionSerializer::encode('RemoveAttribute', RemoveCollectionAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: null,
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

        Attribute::factory([
            'collection_id' => $collectionId = $collection->id,
            'token_id' => null,
            'key' => $key = fake()->word(),
            'id' => "{$collectionId}-" . HexConverter::stringToHexPrefixed($key),
        ])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'key' => $key,
            'signingAccount' => SS58Address::encode($ownerId),
        ]);

        $encodedData = TransactionSerializer::encode('RemoveAttribute', RemoveCollectionAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: null,
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

        Attribute::factory([
            'collection_id' => $collectionId = $collection->id,
            'token_id' => null,
            'key' => $key = fake()->word(),
            'id' => "{$collectionId}-" . HexConverter::stringToHexPrefixed($key),
        ])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'key' => $key,
            'signingAccount' => $ownerId,
        ]);

        $encodedData = TransactionSerializer::encode('RemoveAttribute', RemoveCollectionAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: null,
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

    public function test_it_can_remove_an_attribute_with_null_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $this->collection->id,
            'tokenId' => null,
            'key' => $key = $this->attribute->key,
        ]);

        $encodedData = TransactionSerializer::encode('RemoveAttribute', RemoveCollectionAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: null,
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

    public function test_it_can_remove_an_attribute_with_bigint_collection_id(): void
    {
        $this->deleteAllFrom($collectionId = Hex::MAX_UINT128);

        $collection = Collection::factory([
            'owner_id' => $this->wallet,
            'id' => $collectionId,
        ])->create();

        $attribute = Attribute::factory([
            'collection_id' => $collection,
            'token_id' => null,
            'key' => $key = fake()->word(),
            'id' => "{$collectionId}-" . HexConverter::stringToHexPrefixed($key),
        ])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => null,
            'key' => $key,
        ]);

        $encodedData = TransactionSerializer::encode('RemoveAttribute', RemoveCollectionAttributeMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: null,
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
        $this->deleteAllFrom($collectionId = fake()->numberBetween(2000));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'key' => $this->attribute->key,
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
            'key' => $this->attribute->key,
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
            'key' => $this->attribute->key,
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of required type "BigInt!" was not provided.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_null_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => null,
            'key' => $this->attribute->key,
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of non-null type "BigInt!" must not be null.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_no_key(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
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
            'key' => $key,
        ], true);

        $this->assertArrayContainsArray(
            ['key' => ['The key does not exist in the specified collection.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
