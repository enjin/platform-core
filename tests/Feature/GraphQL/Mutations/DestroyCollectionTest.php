<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Services\Database\WalletService;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

class DestroyCollectionTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;

    protected string $method = 'DestroyCollection';

    protected Codec $codec;
    protected string $defaultAccount;
    protected Model $collection;
    protected Model $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new Codec();
        $walletService = new WalletService();

        $this->defaultAccount = config('enjin-platform.chains.daemon-account');
        $this->owner = $walletService->firstOrStore(['public_key' => $this->defaultAccount]);
        $this->collection = Collection::factory()->create([
            'owner_wallet_id' => $this->owner->id,
        ]);
    }

    // Happy Path
    public function test_it_can_destroy_a_collection(): void
    {
        $encodedData = $this->codec->encode()->destroyCollection($this->collection->collection_chain_id);

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
        ]);

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
        $encodedData = $this->codec->encode()->destroyCollection($this->collection->collection_chain_id);

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
        ]);

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

    public function test_it_will_fail_with_collection_id_less_than_two_thousand(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => fake()->numberBetween(0, 1999),
        ], true);

        $this->assertArraySubset(
            ['collectionId' => ['The collection id is too small, the minimum value it can be is 2000.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_overflow_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => Hex::MAX_UINT256,
        ], true);

        $this->assertArraySubset(
            ['collectionId' => ['The collection id is too large, the maximum value it can be is 340282366920938463463374607431768211455.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_if_collection_id_doesnt_exists(): void
    {
        Collection::where('collection_chain_id', '=', $collectionId = fake()->numberBetween(1))?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
        ], true);

        $this->assertArraySubset(
            ['collectionId' => ['The selected collection id is invalid.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_if_destroy_a_collection_that_has_tokens(): void
    {
        $collection = Collection::factory(['owner_wallet_id' => $this->owner->id])->create();
        Token::factory(['collection_id' => $collection])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collection->collection_chain_id,
        ], true);

        $this->assertArraySubset(
            ['collectionId' => ['The collection id must not have any existing tokens.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_trying_to_destroy_a_collection_owned_by_another_person(): void
    {
        $collection = Collection::factory()->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collection->collection_chain_id,
        ], true);

        $this->assertArraySubset(
            ['collectionId' => ['The collection id provided is not owned by you.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
