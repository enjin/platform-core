<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\SetCollectionAttributeMutation;
use Enjin\Platform\Models\Indexer\Account;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Indexer\Token;
use Enjin\Platform\Rules\IsCollectionOwner;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Support\Address;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Support\MocksHttpClient;
use Facades\Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Faker\Generator;
use Illuminate\Support\Facades\Event;
use Override;

class SetCollectionAttributeTest extends TestCaseGraphQL
{
    use MocksHttpClient;

    protected string $method = 'SetCollectionAttribute';
    protected Codec $codec;
    protected Collection $collection;
    protected Address $wallet;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->codec = new Codec();
        $this->wallet = Address::daemon();
        $this->collection = Collection::factory()->create(['owner_id' => $this->wallet]);
    }

    // Happy Path

    public function test_it_can_bypass_ownership(): void
    {
        $signingWallet = Address::factory()->create();
        $collection = Collection::factory()->create(['owner_id' => $signingWallet]);
        Token::factory([
            'collection_id' => $collection,
        ])->create();

        $response = $this->graphql($this->method, $params = [
            'collectionId' => $collection->collection_chain_id,
            'key' => fake()->word(),
            'value' => fake()->realText(),
            'simulate' => null,
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

    public function test_it_can_create_an_attribute_with_ss58_signing_account(): void
    {
        $signingWallet = Address::factory()->create([
            'public_key' => $signingAccount = app(Generator::class)->public_key(),
        ]);
        $collection = Collection::factory()->create(['owner_id' => $signingWallet]);
        $response = $this->graphql($this->method, [
            'collectionId' => $collection->collection_chain_id,
            'key' => $key = fake()->word(),
            'value' => $value = fake()->realText(),
            'signingAccount' => SS58Address::encode($signingAccount),
        ]);

        $encodedData = TransactionSerializer::encode('SetAttribute', SetCollectionAttributeMutation::getEncodableParams(
            collectionId: $collection->collection_chain_id,
            tokenId: null,
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
        $signingWallet = Address::factory()->create([
            'public_key' => $signingAccount = app(Generator::class)->public_key(),
        ]);
        $collection = Collection::factory()->create(['owner_id' => $signingWallet]);
        $response = $this->graphql($this->method, [
            'collectionId' => $collection->collection_chain_id,
            'key' => $key = fake()->word(),
            'value' => $value = fake()->realText(),
            'signingAccount' => $signingAccount,
        ]);

        $encodedData = TransactionSerializer::encode('SetAttribute', SetCollectionAttributeMutation::getEncodableParams(
            collectionId: $collection->collection_chain_id,
            tokenId: null,
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

    // Exception Path
    public function test_it_will_fail_with_invalid_key_length(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
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
            'collectionId' => $this->collection->collection_chain_id,
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
        Collection::where('collection_chain_id', '=', $collectionId = fake()->numberBetween(2000))?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
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
