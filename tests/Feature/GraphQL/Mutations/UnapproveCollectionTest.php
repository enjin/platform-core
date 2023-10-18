<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\CollectionAccount;
use Enjin\Platform\Models\CollectionAccountApproval;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Services\Database\WalletService;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Support\MocksWebsocketClient;
use Facades\Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

class UnapproveCollectionTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;
    use MocksWebsocketClient;

    protected string $method = 'UnapproveCollection';
    protected Codec $codec;
    protected string $defaultAccount;
    protected Model $owner;
    protected Model $operator;
    protected Model $collection;
    protected Model $collectionAccount;
    protected Model $collectionAccountApproval;

    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new Codec();
        $walletService = new WalletService();
        $this->defaultAccount = Account::daemonPublicKey();
        $this->owner = $walletService->firstOrStore(['public_key' => $this->defaultAccount]);

        $this->collection = Collection::factory()->create([
            'owner_wallet_id' => $this->owner->id,
        ]);

        $this->collectionAccount = CollectionAccount::factory()->create([
            'collection_id' => $this->collection->id,
            'wallet_id' => $this->owner->id,
        ]);

        $this->collectionAccountApproval = CollectionAccountApproval::factory()->create([
            'collection_account_id' => $this->collectionAccount->id,
        ]);

        $this->operator = Wallet::find($this->collectionAccountApproval->wallet_id);
    }

    // Happy Path

    public function test_it_can_skip_validation(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = random_int(1, 100),
            'operator' => SS58Address::encode($this->operator->public_key),
            'skipValidation' => true,
        ]);

        $encodedData = $this->codec->encoder()->unapproveCollection(
            $collectionId,
            $this->operator->public_key,
        );

        $this->assertArraySubset([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => [
                'account' => [
                    'publicKey' => $this->defaultAccount,
                ],
            ],
        ], $response);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_simulate(): void
    {
        $this->mockFee($feeDetails = app(Generator::class)->fee_details());
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'operator' => SS58Address::encode($this->operator->public_key),
            'simulate' => true,
        ]);

        $encodedData = $this->codec->encoder()->unapproveCollection(
            $this->collection->collection_chain_id,
            $this->operator->public_key,
        );

        $this->assertArraySubset([
            'id' => null,
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'fee' => $feeDetails['fakeSum'],
            'deposit' => null,
            'wallet' => [
                'account' => [
                    'publicKey' => $this->defaultAccount,
                ],
            ],
        ], $response);

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_can_unapprove_a_collection_with_string(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'operator' => SS58Address::encode($this->operator->public_key),
            'nonce' => $nonce = fake()->numberBetween(),
        ]);

        $encodedData = $this->codec->encode()->unapproveCollection(
            $this->collection->collection_chain_id,
            $this->operator->public_key,
        );

        $this->assertArraySubset([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'signingPayload' => Substrate::getSigningPayload($encodedData, [
                'nonce' => $nonce,
                'tip' => '0',
            ]),
            'wallet' => [
                'account' => [
                    'publicKey' => $this->defaultAccount,
                ],
            ],
        ], $response);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_unapprove_a_collection_with_ss58_signing_account(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'operator' => SS58Address::encode($this->operator->public_key),
            'signingAccount' => SS58Address::encode($signingAccount = app(Generator::class)->public_key),
        ]);

        $encodedData = $this->codec->encoder()->unapproveCollection(
            $this->collection->collection_chain_id,
            $this->operator->public_key,
        );

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

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_unapprove_a_collection_with_public_key_signing_account(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'operator' => SS58Address::encode($this->operator->public_key),
            'signingAccount' => $signingAccount = app(Generator::class)->public_key,
        ]);

        $encodedData = $this->codec->encode()->unapproveCollection(
            $this->collection->collection_chain_id,
            $this->operator->public_key,
        );

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

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_unapprove_a_collection_with_int(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => (int) $this->collection->collection_chain_id,
            'operator' => SS58Address::encode($this->operator->public_key),
        ]);

        $encodedData = $this->codec->encoder()->unapproveCollection(
            $this->collection->collection_chain_id,
            $this->operator->public_key,
        );

        $this->assertArraySubset([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => [
                'account' => [
                    'publicKey' => $this->defaultAccount,
                ],
            ],
        ], $response);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_unapprove_a_collection_with_bigint(): void
    {
        $collection = Collection::factory()->create([
            'collection_chain_id' => Hex::MAX_UINT128,
            'owner_wallet_id' => $this->owner->id,
        ]);

        $collectionAccount = CollectionAccount::find($this->collectionAccount->id);
        $collectionAccount->collection_id = $collection->id;
        $collectionAccount->save();

        $response = $this->graphql($this->method, [
            'collectionId' => $collection->collection_chain_id,
            'operator' => SS58Address::encode($this->operator->public_key),
        ]);

        $encodedData = $this->codec->encoder()->unapproveCollection(
            $collection->collection_chain_id,
            $this->operator->public_key,
        );

        $this->assertArraySubset([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => [
                'account' => [
                    'publicKey' => $this->defaultAccount,
                ],
            ],
        ], $response);

        Event::assertDispatched(TransactionCreated::class);
    }

    // Exception Path

    public function test_it_fail_with_no_collection_id(): void
    {
        $response = $this->graphql($this->method, [], true);

        $this->assertEquals(
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

        $this->assertEquals(
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

        $this->assertEquals(
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

        $this->assertEquals(
            'Variable "$operator" of non-null type "String!" must not be null.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_invalid_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => 'abc',
            'operator' => SS58Address::encode($this->operator->public_key),
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
            'operator' => SS58Address::encode($this->operator->public_key),
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
            'collectionId' => $this->collection->collection_chain_id,
            'operator' => 'not_a_substrate_address',
        ], true);

        $this->assertArraySubset(
            ['operator' => ['The operator is not a valid substrate account.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_collection_id_doesnt_exists(): void
    {
        Collection::where('collection_chain_id', '=', $collectionId = fake()->numberBetween(1))?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'operator' => SS58Address::encode($this->operator->public_key),
        ], true);

        $this->assertArraySubset(
            ['collectionId' => ['The selected collection id is invalid.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_operator_doesnt_exists(): void
    {
        Wallet::where('public_key', '=', $operator = app(Generator::class)->public_key())?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $this->collection->collection_chain_id,
            'operator' => SS58Address::encode($operator),
        ], true);

        $this->assertStringContainsString(
            'Could not find an approval for',
            $response['error']['operator'][0]
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
