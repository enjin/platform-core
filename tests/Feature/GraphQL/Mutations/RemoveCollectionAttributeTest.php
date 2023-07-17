<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Models\Attribute;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

class RemoveCollectionAttributeTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;

    protected string $method = 'RemoveCollectionAttribute';
    protected Codec $codec;
    protected string $defaultAccount;
    protected Model $collection;
    protected Model $attribute;

    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new Codec();
        $this->defaultAccount = Account::daemonPublicKey();

        $this->attribute = Attribute::factory([
            'token_id' => null,
        ])->create();

        $this->collection = Collection::find($this->attribute->collection_id);
    }

    // Happy Path

    public function test_it_can_skip_validation(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = random_int(1, 1000),
            'key' => $key = $this->attribute->key,
            'skipValidation' => true,
        ]);

        $encodedData = $this->codec->encode()->removeAttribute(
            $collectionId,
            null,
            $key,
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

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_remove_an_attribute(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $this->collection->collection_chain_id,
            'key' => $key = $this->attribute->key,
        ]);

        $encodedData = $this->codec->encode()->removeAttribute(
            $collectionId,
            null,
            $key,
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
            'collectionId' => $collectionId = $this->collection->collection_chain_id,
            'tokenId' => null,
            'key' => $key = $this->attribute->key,
        ]);

        $encodedData = $this->codec->encode()->removeAttribute(
            $collectionId,
            null,
            $key,
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
        $collection = Collection::factory([
            'collection_chain_id' => $collectionId = Hex::MAX_UINT128,
        ])->create();

        $attribute = Attribute::factory([
            'collection_id' => $collection,
            'token_id' => null,
        ])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => null,
            'key' => $key = $attribute->key,
        ]);

        $encodedData = $this->codec->encode()->removeAttribute(
            $collectionId,
            null,
            $key,
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
        Collection::where('collection_chain_id', '=', $collectionId = fake()->numberBetween(2000))?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'key' => $this->attribute->key,
        ], true);

        $this->assertArraySubset(
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
            'collectionId' => $this->collection->collection_chain_id,
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
            'collectionId' => $this->collection->collection_chain_id,
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
            'collectionId' => $this->collection->collection_chain_id,
            'key' => '',
        ], true);

        $this->assertArraySubset(
            ['key' => ['The key field must have a value.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_key_doesnt_exists(): void
    {
        Attribute::where('key', '=', $key = fake()->word())?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'key' => $key,
        ], true);

        $this->assertArraySubset(
            ['key' => ['The key does not exist in the specified collection.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
