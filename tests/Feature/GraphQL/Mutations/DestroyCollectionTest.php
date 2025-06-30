<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\DestroyCollectionMutation;
use Enjin\Platform\Models\Indexer\Account;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Indexer\Token;
use Enjin\Platform\Rules\IsCollectionOwner;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Support\Address;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Support\MocksHttpClient;
use Facades\Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Faker\Generator;
use Illuminate\Support\Facades\Event;
use Override;

class DestroyCollectionTest extends TestCaseGraphQL
{
    use MocksHttpClient;

    protected string $method = 'DestroyCollection';

    protected Codec $codec;
    protected Collection $collection;
    protected Account $owner;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new Codec();
        $this->owner = Address::daemon();
        $this->collection = Collection::factory()->create([
            'owner_id' => $this->owner->id,
        ]);
    }

    // Happy Path
    public function test_it_can_skip_validation(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, DestroyCollectionMutation::getEncodableParams(
            collectionId: $collectionId = fake()->randomNumber(),
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'skipValidation' => true,
            'simulate' => null,
        ]);

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
        $encodedData = TransactionSerializer::encode($this->method, DestroyCollectionMutation::getEncodableParams(
            collectionId: $this->collection->id
        ));

        $this->mockFee($feeDetails = app(Generator::class)->fee_details());
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'simulate' => true,
        ]);

        $this->assertArrayContainsArray([
            'id' => null,
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'fee' => (string) $feeDetails['fakeSum'],
            'deposit' => null,
            'wallet' => null,
        ], $response);

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_can_bypass_ownership(): void
    {
        $collection = Collection::factory()->create(['owner_id' => Account::factory()->create()]);

        $response = $this->graphql($this->method, $params = [
            'collectionId' => $collection->id,
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

    public function test_it_can_destroy_a_collection(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, DestroyCollectionMutation::getEncodableParams(
            collectionId: $this->collection->id
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'nonce' => $nonce = fake()->numberBetween(),
        ]);

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

    public function test_it_can_destroy_a_collection_with_ss58_signing_account(): void
    {
        $wallet = Account::factory([
            'id' => $signingAccount = app(Generator::class)->public_key,
        ])->create();

        $collection = Collection::factory([
            'owner_id' => $wallet,
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, DestroyCollectionMutation::getEncodableParams(
            collectionId: $collection->id
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collection->id,
            'signingAccount' => SS58Address::encode($signingAccount),
        ]);

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

    public function test_it_can_destroy_a_collection_with_public_key_signing_account(): void
    {
        $wallet = Account::factory([
            'id' => $signingAccount = app(Generator::class)->public_key,
        ])->create();

        $collection = Collection::factory([
            'owner_id' => $wallet,
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, DestroyCollectionMutation::getEncodableParams(
            collectionId: $collection->id
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collection->id,
            'signingAccount' => $signingAccount,
        ]);

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

    public function test_it_can_destroy_a_collection_with_bigint(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, DestroyCollectionMutation::getEncodableParams(
            collectionId: $this->collection->id
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
        ]);

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
    public function test_it_will_fail_with_no_args(): void
    {
        $response = $this->graphql($this->method, [], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of required type "BigInt!" was not provided.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_collection_id_null(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => null,
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of non-null type "BigInt!" must not be null',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => 'invalid',
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value "invalid"; Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_negative_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => '-1',
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value "-1"; Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_overflow_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => Hex::MAX_UINT256,
        ], true);

        $this->assertArrayContainsArray(
            ['collectionId' => ['The collection id is too large, the maximum value it can be is 340282366920938463463374607431768211455.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_if_collection_id_doesnt_exists(): void
    {
        Collection::where('id', '=', $collectionId = fake()->numberBetween(1))?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
        ], true);

        $this->assertArrayContainsArray(
            ['collectionId' => ['The selected collection id is invalid.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_if_destroy_a_collection_that_has_tokens(): void
    {
        $collection = Collection::factory(['owner_id' => $this->owner->id])->create();
        Token::factory(['collection_id' => $collection])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collection->id,
        ], true);

        $this->assertArrayContainsArray(
            ['collectionId' => ['The collection id must not have any existing tokens.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_trying_to_destroy_a_collection_owned_by_another_person(): void
    {
        $collection = Collection::factory()->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collection->id,
        ], true);

        $this->assertArrayContainsArray(
            ['collectionId' => ['The collection id provided is not owned by you.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
