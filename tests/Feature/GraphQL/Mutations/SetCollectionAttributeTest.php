<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\SetCollectionAttributeMutation;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Support\MocksWebsocketClient;
use Facades\Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

class SetCollectionAttributeTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;
    use MocksWebsocketClient;

    protected string $method = 'SetCollectionAttribute';
    protected Codec $codec;
    protected string $defaultAccount;
    protected Model $collection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new Codec();
        $this->defaultAccount = Account::daemonPublicKey();
        $this->collection = Collection::factory()->create();
    }

    // Happy Path

    public function test_it_can_create_an_attribute(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'key' => $key = fake()->word(),
            'value' => $value = fake()->realText(),
            'simulate' => null,
            'nonce' => $nonce = fake()->numberBetween(),
        ]);

        $encodedData = TransactionSerializer::encode('SetAttribute', SetCollectionAttributeMutation::getEncodableParams(
            collectionId: $this->collection->collection_chain_id,
            tokenId: null,
            key: $key,
            value: $value
        ));

        $this->assertArraySubset([
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

    public function test_it_can_create_an_attribute_with_ss58_signing_account(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'key' => $key = fake()->word(),
            'value' => $value = fake()->realText(),
            'signingAccount' => SS58Address::encode($signingAccount = app(Generator::class)->public_key),
        ]);

        $encodedData = TransactionSerializer::encode('SetAttribute', SetCollectionAttributeMutation::getEncodableParams(
            collectionId: $this->collection->collection_chain_id,
            tokenId: null,
            key: $key,
            value: $value
        ));

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

    public function test_it_can_create_an_attribute_with_public_key_signing_account(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'key' => $key = fake()->word(),
            'value' => $value = fake()->realText(),
            'signingAccount' => $signingAccount = app(Generator::class)->public_key,
        ]);

        $encodedData = TransactionSerializer::encode('SetAttribute', SetCollectionAttributeMutation::getEncodableParams(
            collectionId: $this->collection->collection_chain_id,
            tokenId: null,
            key: $key,
            value: $value
        ));

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

    public function test_it_can_simulate(): void
    {
        $this->mockFee($feeDetails = app(Generator::class)->fee_details());
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'key' => $key = fake()->word(),
            'value' => $value = fake()->realText(),
            'simulate' => true,
        ]);

        $encodedData = TransactionSerializer::encode('SetAttribute', SetCollectionAttributeMutation::getEncodableParams(
            collectionId: $this->collection->collection_chain_id,
            tokenId: null,
            key: $key,
            value: $value
        ));

        $this->assertIsNumeric($response['deposit']);
        $this->assertArraySubset([
            'id' => null,
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'fee' => $feeDetails['fakeSum'],
            'wallet' => null,
        ], $response);

        Event::assertNotDispatched(TransactionCreated::class);
    }

    // Exception Path

    public function test_it_fail_with_for_collection_that_doesnt_exists(): void
    {
        Collection::where('collection_chain_id', '=', $collectionId = fake()->numberBetween(2000))?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'key' => fake()->word(),
            'value' => fake()->realText(),
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
            'key' => fake()->word(),
            'value' => fake()->realText(),
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
            'collectionId' => $this->collection->collection_chain_id,
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
            'collectionId' => $this->collection->collection_chain_id,
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
            'collectionId' => $this->collection->collection_chain_id,
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
