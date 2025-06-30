<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\ToFixMutations;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\AcceptCollectionTransferMutation;
use Enjin\Platform\Models\Indexer\Account;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Support\Address;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Support\MocksHttpClient;
use Facades\Enjin\Platform\Facades\TransactionSerializer;
use Facades\Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Faker\Generator;
use Illuminate\Support\Facades\Event;
use Override;

class AcceptCollectionTransferTest extends TestCaseGraphQL
{
    use MocksHttpClient;

    protected string $method = 'AcceptCollectionTransfer';
    protected Codec $codec;

    protected Address $owner;
    protected Collection $collection;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new Codec();
        $this->owner = Address::daemon();
        $this->collection = Collection::factory()->create([
            'pending_transfer' => $this->owner->public_key,
        ]);
    }

    // Happy Path
    public function test_it_can_skip_validation(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = random_int(1, 1000),
            'skipValidation' => true,
            'simulate' => null,
        ]);

        $encodedData = TransactionSerializer::encode($this->method, AcceptCollectionTransferMutation::getEncodableParams(
            collectionId: $collectionId,
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
            'collectionId' => $this->collection->collection_chain_id,
            'simulate' => true,
        ]);

        $encodedData = TransactionSerializer::encode($this->method, AcceptCollectionTransferMutation::getEncodableParams(
            collectionId: $this->collection->collection_chain_id,
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

    public function test_it_can_accept_transfer(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'nonce' => $nonce = fake()->numberBetween(),
        ]);

        $encodedData = TransactionSerializer::encode($this->method, AcceptCollectionTransferMutation::getEncodableParams(
            collectionId: $this->collection->collection_chain_id,
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

    public function test_it_can_accept_with_signing_account(): void
    {
        Address::factory()->create([
            'public_key' => $signingAccount = app(Generator::class)->public_key(),
        ]);
        $collection = Collection::factory(['pending_transfer' => $signingAccount])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collection->collection_chain_id,
            'signingAccount' => $signingAccount,
        ]);

        $encodedData = TransactionSerializer::encode($this->method, AcceptCollectionTransferMutation::getEncodableParams(
            collectionId: $collection->collection_chain_id,
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

    public function test_it_can_accept_a_collection_with_bigint(): void
    {
        Collection::where('collection_chain_id', '=', Hex::MAX_UINT128)->delete();

        $collection = Collection::factory()->create([
            'collection_chain_id' => Hex::MAX_UINT128,
            'pending_transfer' => $this->owner->public_key,
        ]);
        $this->assertDatabaseHas('collections', [
            'id' => $collection->id,
            'collection_chain_id' => $collection->collection_chain_id,
            'pending_transfer' => $this->owner->public_key,
        ]);

        $response = $this->graphql($this->method, [
            'collectionId' => $collection->collection_chain_id,
        ]);

        $encodedData = TransactionSerializer::encode($this->method, AcceptCollectionTransferMutation::getEncodableParams(
            collectionId: $collection->collection_chain_id,
        ));

        $this->assertArrayContainsArray([
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        Event::assertDispatched(TransactionCreated::class);
    }

    // Exception Path
    public function test_it_fails_when_collection_does_not_exist(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => random_int(1, 1000),
        ], true);

        $this->assertArrayContainsArray([
            'collectionId' => ['The selected collection id is invalid.'],
        ], $response['error']);
    }

    public function test_it_fails_with_invalid_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => 'invalid',
        ], true);

        $this->assertStringContainsString(
            'Cannot represent following value as uint256',
            $response['error']
        );
    }

    public function test_it_fails_with_null_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => null,
        ], true);

        $this->assertStringContainsString('collectionId', $response['error']);
    }

    public function test_it_fails_with_no_collection_id(): void
    {
        $response = $this->graphql($this->method, [], true);

        $this->assertStringContainsString(
            'collectionId',
            $response['error']
        );
    }

    public function test_it_fails_with_no_pending_transfer(): void
    {
        $collection = Collection::factory()->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $collection->collection_chain_id,
        ], true);

        $this->assertArrayContainsArray([
            'collectionId' => [sprintf('There is no pending collection transfer for the account %s at collection %s.', Address::daemonPublicKey(), $collectionId)],
        ], $response['error']);
    }

    public function test_it_fails_with_another_address_in_pending_transfer(): void
    {
        $collection = Collection::factory()->create([
            'pending_transfer' => app(Generator::class)->public_key(),
        ]);

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $collection->collection_chain_id,
        ], true);

        $this->assertArrayContainsArray(
            ['collectionId' => [sprintf('There is no pending collection transfer for the account %s at collection %s.', Address::daemonPublicKey(), $collectionId)]],
            $response['error']
        );
    }
}
