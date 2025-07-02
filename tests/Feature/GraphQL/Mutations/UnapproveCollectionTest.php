<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\UnapproveCollectionMutation;
use Enjin\Platform\Models\Indexer\Account;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Indexer\CollectionAccount;
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

class UnapproveCollectionTest extends TestCaseGraphQL
{
    use MocksHttpClient;

    protected string $method = 'UnapproveCollection';
    protected Codec $codec;

    protected Account $owner;
    protected Account $operator;
    protected Collection $collection;
    protected CollectionAccount $collectionAccount;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new Codec();
        $this->owner = $this->getDaemonAccount();

        $this->collection = Collection::factory([
            'owner_id' => $ownerId = $this->owner->id,
        ])->create();

        $this->collectionAccount = CollectionAccount::factory()->create([
            'collection_id' => $collectionId = $this->collection->id,
            'account_id' => $ownerId,
            'id' => "{$ownerId}-{$collectionId}",
            'approvals' => [['accountId' => ($this->operator = Account::factory()->create())->id]],
        ]);
    }

    // Happy Path

    public function test_it_can_skip_validation(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = random_int(1, 100),
            'operator' => SS58Address::encode($this->operator->id),
            'skipValidation' => true,
        ]);

        $encodedData = TransactionSerializer::encode($this->method, UnapproveCollectionMutation::getEncodableParams(
            collectionId: $collectionId,
            operator: $this->operator->id
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
            'collectionId' => $this->collection->id,
            'operator' => SS58Address::encode($this->operator->id),
            'simulate' => true,
        ]);

        $encodedData = TransactionSerializer::encode($this->method, UnapproveCollectionMutation::getEncodableParams(
            collectionId: $this->collection->id,
            operator: $this->operator->id
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

        CollectionAccount::factory([
            'collection_id' => $collectionId = $collection->id,
            'account_id' => $ownerId,
            'id' => "{$ownerId}-{$collectionId}",
            'account_count' => 1,
            'approvals' => [['accountId' => $operatorId = Account::factory()->create()->id]],
        ])->create();

        $response = $this->graphql($this->method, $params = [
            'collectionId' => $collectionId,
            'operator' => SS58Address::encode($operatorId),
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

    public function test_it_can_unapprove_a_collection_with_string(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, UnapproveCollectionMutation::getEncodableParams(
            collectionId: $this->collection->id,
            operator: $this->operator->id,
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'operator' => SS58Address::encode($this->operator->id),
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

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_unapprove_a_collection_with_ss58_signing_account(): void
    {
        Account::factory([
            'id' => $ownerId = app(Generator::class)->public_key(),
        ])->create();

        $collection = Collection::factory([
            'owner_id' => $ownerId,
        ])->create();

        CollectionAccount::factory([
            'collection_id' => $collectionId = $collection->id,
            'account_id' => $ownerId,
            'id' => "{$ownerId}-{$collectionId}",
            'account_count' => 1,
            'approvals' => [['accountId' => $operatorId = $this->operator->id]],
        ])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'operator' => SS58Address::encode($operatorId),
            'signingAccount' => SS58Address::encode($ownerId),
        ]);

        $encodedData = TransactionSerializer::encode($this->method, UnapproveCollectionMutation::getEncodableParams(
            collectionId: $collectionId,
            operator: $operatorId
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

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_unapprove_a_collection_with_public_key_signing_account(): void
    {
        $signingAccount = Account::factory([
            'id' => $ownerId = app(Generator::class)->public_key(),
        ])->create();

        $collection = Collection::factory([
            'owner_id' => $ownerId,
        ])->create();

        CollectionAccount::factory([
            'collection_id' => $collectionId = $collection->id,
            'account_id' => $ownerId,
            'id' => "{$ownerId}-{$collectionId}",
            'approvals' => [['accountId' => $operatorId = $this->operator->id]],
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, UnapproveCollectionMutation::getEncodableParams(
            collectionId: $collectionId,
            operator: $operatorId,
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'operator' => SS58Address::encode($operatorId),
            'signingAccount' => $ownerId,
        ]);

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

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_unapprove_a_collection_with_int(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => (int) $this->collection->id,
            'operator' => SS58Address::encode($this->operator->id),
        ]);

        $encodedData = TransactionSerializer::encode($this->method, UnapproveCollectionMutation::getEncodableParams(
            collectionId: $this->collection->id,
            operator: $this->operator->id
        ));

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_unapprove_a_collection_with_bigint(): void
    {
        $this->deleteAllFrom($collectionId = Hex::MAX_UINT128);

        Collection::factory([
            'id' => $collectionId,
            'owner_id' => $ownerId = $this->owner->id,
        ])->create();

        CollectionAccount::factory([
            'collection_id' => $collectionId,
            'account_id' => $ownerId,
            'id' => "{$ownerId}-{$collectionId}",
            'approvals' => [['accountId' => $operatorId = $this->operator->id]],
        ])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'operator' => SS58Address::encode($operatorId),
        ]);

        $encodedData = TransactionSerializer::encode($this->method, UnapproveCollectionMutation::getEncodableParams(
            collectionId: $collectionId,
            operator: $operatorId
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

    public function test_it_fail_with_no_collection_id(): void
    {
        $response = $this->graphql($this->method, [], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of required type "BigInt!" was not provided.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_collection_id_null(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => null,
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of non-null type "BigInt!" must not be null.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_no_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => fake()->numberBetween(1),
        ], true);

        $this->assertStringContainsString(
            'Variable "$operator" of required type "String!" was not provided.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_operator_null(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => fake()->numberBetween(1),
            'operator' => null,
        ], true);

        $this->assertStringContainsString(
            'Variable "$operator" of non-null type "String!" must not be null.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_invalid_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => 'abc',
            'operator' => SS58Address::encode($this->operator->id),
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value "abc"; Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_negative_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => -1,
            'operator' => SS58Address::encode($this->operator->id),
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value -1; Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_invalid_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'operator' => 'not_a_substrate_address',
        ], true);

        $this->assertArrayContainsArray(
            ['operator' => ['The operator is not a valid substrate account.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_collection_id_doesnt_exists(): void
    {
        $this->deleteAllFrom($collectionId = fake()->numberBetween());

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'operator' => SS58Address::encode($this->operator->id),
        ], true);

        $this->assertArrayContainsArray(
            ['collectionId' => ['The selected collection id is invalid.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_operator_doesnt_exists(): void
    {
        Account::find($accountId = app(Generator::class)->public_key())?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $this->collection->id,
            'operator' => $operatorId = SS58Address::encode($accountId),
        ], true);

        $this->assertArrayContainsArray([
            'operator' => ["Could not find an approval for {$operatorId} at collection {$collectionId}."],
        ], $response['error']);

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
