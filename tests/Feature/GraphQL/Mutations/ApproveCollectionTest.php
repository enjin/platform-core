<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\ApproveCollectionMutation;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Laravel\Token;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Rules\IsCollectionOwner;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Support\MocksHttpClient;
use Facades\Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

class ApproveCollectionTest extends TestCaseGraphQL
{
    use MocksHttpClient;

    protected string $method = 'ApproveCollection';
    protected Codec $codec;
    protected Model $owner;
    protected Model $collection;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new Codec();
        $this->owner = Account::daemon();
        $this->collection = Collection::factory()->create(['owner_wallet_id' => $this->owner->id]);
        Token::factory(fake()->numberBetween(1, 2))->create([
            'collection_id' => $this->collection->id,
        ]);
    }

    // Happy Path
    public function test_it_can_skip_validation(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = random_int(1, 1000),
            'operator' => $operator = app(Generator::class)->public_key(),
            'skipValidation' => true,
            'simulate' => null,
        ]);

        $encodedData = TransactionSerializer::encode($this->method, ApproveCollectionMutation::getEncodableParams(
            collectionId: $collectionId,
            operator: $operator
        ));

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_bypass_ownership(): void
    {
        Token::factory([
            'collection_id' => $collection = Collection::factory()->create(['owner_wallet_id' => Wallet::factory()->create()]),
        ])->create();

        $response = $this->graphql($this->method, $params = [
            'collectionId' => $collection->collection_chain_id,
            'operator' => fake()->text(),
        ], true);
        $this->assertEquals(
            [
                'collectionId' => ['The collection id provided is not owned by you.'],
                'operator' => ['The operator is not a valid substrate account.'],
            ],
            $response['error']
        );

        IsCollectionOwner::bypass();
        $response = $this->graphql($this->method, $params, true);
        $this->assertEquals(
            ['operator' => ['The operator is not a valid substrate account.']],
            $response['error']
        );
        IsCollectionOwner::unBypass();
    }

    public function test_it_can_simulate(): void
    {
        $this->mockFee($feeDetails = app(Generator::class)->fee_details());
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'operator' => $operator = app(Generator::class)->public_key(),
            'simulate' => true,
        ]);

        $encodedData = TransactionSerializer::encode($this->method, ApproveCollectionMutation::getEncodableParams(
            collectionId: $this->collection->collection_chain_id,
            operator: $operator
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

    public function test_it_can_approve_a_collection_with_any_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'operator' => $operator = app(Generator::class)->public_key(),
            'nonce' => $nonce = fake()->numberBetween(),
        ]);

        $encodedData = TransactionSerializer::encode($this->method, ApproveCollectionMutation::getEncodableParams(
            collectionId: $this->collection->collection_chain_id,
            operator: $operator,
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

    public function test_it_can_approve_with_signing_account_ss58(): void
    {
        $newOwner = Wallet::factory()->create([
            'public_key' => $signingAccount = app(Generator::class)->public_key(),
        ]);
        Token::factory([
            'collection_id' => $collection = Collection::factory(['owner_wallet_id' => $newOwner])->create(),
        ])->create();
        $response = $this->graphql($this->method, [
            'collectionId' => $collection->collection_chain_id,
            'operator' => $operator = app(Generator::class)->public_key(),
            'signingAccount' => SS58Address::encode($signingAccount),
        ]);

        $encodedData = TransactionSerializer::encode($this->method, ApproveCollectionMutation::getEncodableParams(
            collectionId: $collection->collection_chain_id,
            operator: $operator
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

    public function test_it_can_approve_with_public_key(): void
    {
        $newOwner = Wallet::factory()->create([
            'public_key' => $signingAccount = app(Generator::class)->public_key(),
        ]);
        Token::factory([
            'collection_id' => $collection = Collection::factory(['owner_wallet_id' => $newOwner])->create(),
        ])->create();
        $response = $this->graphql($this->method, [
            'collectionId' => $collection->collection_chain_id,
            'operator' => $operator = app(Generator::class)->public_key(),
            'signingAccount' => $signingAccount,
        ]);

        $encodedData = TransactionSerializer::encode($this->method, ApproveCollectionMutation::getEncodableParams(
            collectionId: $collection->collection_chain_id,
            operator: $operator,
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

    public function test_it_can_approve_a_collection_with_operator_that_exists_locally(): void
    {
        $operator = Wallet::factory()->create();

        $this->assertDatabaseHas('wallets', [
            'public_key' => $operator->public_key,
        ]);

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'operator' => SS58Address::encode($operator->public_key),
        ]);

        $encodedData = TransactionSerializer::encode($this->method, ApproveCollectionMutation::getEncodableParams(
            collectionId: $this->collection->collection_chain_id,
            operator: $operator->public_key
        ));

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_approve_a_collection_with_operator_that_doesnt_exists_locally_and_creates_it(): void
    {
        Wallet::where('public_key', '=', $operator = app(Generator::class)->public_key())?->delete();

        $this->assertDatabaseMissing('wallets', [
            'public_key' => $operator,
        ]);

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'operator' => SS58Address::encode($operator),
        ]);

        $this->assertDatabaseHas('wallets', [
            'public_key' => $operator,
        ]);

        $encodedData = TransactionSerializer::encode($this->method, ApproveCollectionMutation::getEncodableParams(
            collectionId: $this->collection->collection_chain_id,
            operator: $operator
        ));

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_approve_a_collection_with_expiration(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'operator' => SS58Address::encode($operator = app(Generator::class)->public_key()),
            'expiration' => $expiration = fake()->numberBetween(1),
        ]);

        $encodedData = TransactionSerializer::encode($this->method, ApproveCollectionMutation::getEncodableParams(
            collectionId: $this->collection->collection_chain_id,
            operator: $operator,
            expiration: $expiration
        ));

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_approve_a_collection_with_bigint(): void
    {
        Collection::where('collection_chain_id', '=', Hex::MAX_UINT128)->delete();

        $collection = Collection::factory()->create([
            'collection_chain_id' => Hex::MAX_UINT128,
            'owner_wallet_id' => $this->owner->id,
        ]);
        Token::factory(fake()->numberBetween(1, 10))->create([
            'collection_id' => $collection->id,
        ]);

        $this->assertDatabaseHas('collections', [
            'id' => $collection->id,
            'collection_chain_id' => $collection->collection_chain_id,
            'owner_wallet_id' => $this->owner->id,
        ]);

        $response = $this->graphql($this->method, [
            'collectionId' => $collection->collection_chain_id,
            'operator' => SS58Address::encode($operator = app(Generator::class)->public_key()),
        ]);

        $encodedData = TransactionSerializer::encode($this->method, ApproveCollectionMutation::getEncodableParams(
            collectionId: $collection->collection_chain_id,
            operator: $operator
        ));

        $this->assertArrayContainsArray([
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        Event::assertDispatched(TransactionCreated::class);
    }

    // Exception Path

    public function test_it_will_fail_with_empty_tokens(): void
    {
        $collection = Collection::factory()->create([
            'collection_chain_id' => fake()->numberBetween(5000, 1000),
            'owner_wallet_id' => $this->owner->id,
        ]);

        $response = $this->graphql($this->method, [
            'collectionId' => $collection->collection_chain_id,
            'operator' => app(Generator::class)->public_key(),
        ], true);

        $this->assertArrayContainsArray([
            'collectionId' => ["The collection doesn't have any tokens."],
        ], $response['error']);
    }

    public function test_it_fail_with_no_collection_id(): void
    {
        $response = $this->graphql($this->method, [], true);

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
        ], true);

        $this->assertEquals(
            'Variable "$collectionId" of non-null type "BigInt!" must not be null.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_simulate_invalid(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'operator' => app(Generator::class)->public_key(),
            'simulate' => 'invalid',
        ], true);

        $this->assertStringContainsString(
            'Variable "$simulate" got invalid value "invalid"',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_no_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
        ], true);

        $this->assertEquals(
            'Variable "$operator" of required type "String!" was not provided.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_null_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'operator' => null,
        ], true);

        $this->assertEquals(
            'Variable "$operator" of non-null type "String!" must not be null.',
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

        $this->assertArrayContainsArray(
            ['operator' => ['The operator is not a valid substrate account.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_invalid_expiration(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'operator' => app(Generator::class)->public_key(),
            'expiration' => 'abc',
        ], true);

        $this->assertEquals(
            'Variable "$expiration" got invalid value "abc"; Int cannot represent non-integer value: "abc"',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_negative_expiration(): void
    {
        Block::truncate();
        $block = Block::factory()->create();
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'operator' => app(Generator::class)->public_key(),
            'expiration' => -1,
        ], true);

        $this->assertArrayContainsArray(
            ['expiration' => ["The expiration must be at least {$block->number}."]],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_overlimit_expiration(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'operator' => app(Generator::class)->public_key(),
            'expiration' => Hex::MAX_UINT128,
        ], true);

        $this->assertEquals(
            'Variable "$expiration" got invalid value "340282366920938463463374607431768211455"; Int cannot represent non-integer value: "340282366920938463463374607431768211455"',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_collection_id_that_doesnt_exists(): void
    {
        Collection::where('collection_chain_id', '=', $collectionId = fake()->numberBetween(1))?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'operator' => null,
        ], true);

        $this->assertEquals(
            'Variable "$operator" of non-null type "String!" must not be null.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_if_passing_daemon_as_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'operator' => Account::daemonPublicKey(),
        ], true);

        $this->assertArrayContainsArray(
            ['operator' => ['The operator cannot be set to the daemon account.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_collection_id_non_existent(): void
    {
        Collection::where('collection_chain_id', '=', $collectionId = fake()->numberBetween(2000))?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'operator' => app(Generator::class)->public_key(),
        ], true);

        $this->assertArrayContainsArray(
            ['collectionId' => ['The selected collection id is invalid.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
