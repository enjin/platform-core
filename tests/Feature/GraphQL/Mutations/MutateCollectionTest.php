<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\MutateCollectionMutation;
use Enjin\Platform\Models\Indexer\Account;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Indexer\Token;
use Enjin\Platform\Rules\IsCollectionOwner;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Token\Encoder;
use Enjin\Platform\Services\Token\Encoders\Integer;
use Enjin\Platform\Support\Address;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Support\MocksHttpClient;
use Facades\Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Faker\Generator;
use Illuminate\Support\Facades\Event;
use Override;

class MutateCollectionTest extends TestCaseGraphQL
{
    use MocksHttpClient;

    protected string $method = 'MutateCollection';
    protected Codec $codec;
    protected Collection $collection;
    protected Token $token;
    protected Encoder $tokenIdEncoder;
    protected Address $wallet;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->codec = new Codec();
        $this->wallet = Address::daemon();
        $this->collection = Collection::factory()->create(['owner_id' => $this->wallet]);
        $this->tokenIdEncoder = new Integer();
    }

    // Happy Path

    public function test_it_can_skip_validation(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, MutateCollectionMutation::getEncodableParams(
            collectionId: $collectionId = random_int(1, 1000),
            owner: $owner = Address::daemonPublicKey(),
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'mutation' => [
                'owner' => SS58Address::encode($owner),
            ],
            'skipValidation' => true,
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

    public function test_it_will_fail_with_duplicate_field_names(): void
    {
        self::$queries['MutateCollectionDuplicateFieldName'] = '
            mutation MutateCollection{
                MutateCollection(
                    collectionId: 2750,
                    mutation: {
                        owner:"rf67pPeLYBJRfrehJzzAPVypSCUpPYE62v1gT3f6isBC2EXYe",
                        explicitRoyaltyCurrencies:[
                            {
                                collectionId:12,
                                collectionId:10,
                                collectionId:15,
                                tokenId: 1,
                                tokenId: 2
                            }
                        ]
                    }
                )
            }';
        $response = $this->graphql('MutateCollectionDuplicateFieldName', [], true, ['operationName' => $this->method]);

        $this->assertArrayContainsArray(
            ['collectionId' => ['message' => 'There can be only one input field named "collectionId".']],
            $response['errors']
        );
        $this->assertArrayContainsArray(
            ['tokenId' => ['message' => 'There can be only one input field named "tokenId".']],
            $response['errors']
        );
    }

    public function test_it_can_simulate(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, MutateCollectionMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            owner: $owner = Address::daemonPublicKey(),
        ));

        $this->mockFee($feeDetails = app(Generator::class)->fee_details());
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'mutation' => [
                'owner' => SS58Address::encode($owner),
            ],
            'simulate' => true,
        ]);

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
        $collection = Collection::factory()->create(['owner_id' => Address::factory()->create()]);
        $response = $this->graphql($this->method, $params = [
            'collectionId' => $collection->collection_chain_id,
            'mutation' => [
                'owner' => SS58Address::encode($this->wallet->public_key),
            ],
            'nonce' => $nonce = fake()->numberBetween(),
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

    public function test_it_can_mutate_a_collection_with_owner(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, MutateCollectionMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            owner: $owner = Address::daemonPublicKey(),
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'mutation' => [
                'owner' => SS58Address::encode($owner),
            ],
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

    public function test_it_can_mutate_a_collection_with_ss58_signing_account(): void
    {
        $signingWallet = Address::factory([
            'public_key' => $signingAccount = app(Generator::class)->public_key(),
        ])->create();
        $collection = Collection::factory(['owner_id' => $signingWallet])->create();

        $encodedData = TransactionSerializer::encode($this->method, MutateCollectionMutation::getEncodableParams(
            collectionId: $collectionId = $collection->collection_chain_id,
            owner: $owner = $this->wallet->public_key,
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'mutation' => [
                'owner' => SS58Address::encode($owner),
            ],
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

    public function test_it_can_mutate_a_collection_with_public_key_signing_account(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, MutateCollectionMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            owner: $owner = Address::daemonPublicKey(),
        ));

        $newOwner = Address::factory()->create([
            'public_key' => $signingAccount = app(Generator::class)->public_key(),
        ]);
        $this->collection->update(['owner_id' => $newOwner->id]);
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'mutation' => [
                'owner' => SS58Address::encode($owner),
            ],
            'signingAccount' => $signingAccount,
        ]);
        $this->collection->update(['owner_id' => $this->wallet->id]);

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

    public function test_it_can_mutate_a_collection_with_explicit_royalty_currencies(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, MutateCollectionMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            explicitRoyaltyCurrencies: $currencies = $this->generateCurrencies(fake()->numberBetween(1, 9))
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'mutation' => [
                'explicitRoyaltyCurrencies' => $this->generateEncodeableTokenIds($currencies),
            ],
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

    public function test_it_can_mutate_a_collection_with_owner_and_currencies(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, MutateCollectionMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            owner: $owner = Address::daemonPublicKey(),
            explicitRoyaltyCurrencies: $currencies = $this->generateCurrencies(fake()->numberBetween(1, 9))
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'mutation' => [
                'owner' => SS58Address::encode($owner),
                'explicitRoyaltyCurrencies' => $this->generateEncodeableTokenIds($currencies),
            ],
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

    public function test_it_can_mutate_a_collection_with_big_int_collection_id(): void
    {
        Collection::where('collection_chain_id', Hex::MAX_UINT128)->delete();

        $collection = Collection::factory([
            'collection_chain_id' => Hex::MAX_UINT128,
            'owner_id' => $this->wallet,
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, MutateCollectionMutation::getEncodableParams(
            collectionId: $collectionId = $collection->collection_chain_id,
            owner: $owner = Address::daemonPublicKey(),
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'mutation' => [
                'owner' => SS58Address::encode($owner),
            ],
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

    public function test_it_can_mutate_a_collection_new_owner_doesnt_exists_locally_and_save(): void
    {
        Address::where('public_key', '=', $owner = app(Generator::class)->public_key())?->delete();

        $encodedData = TransactionSerializer::encode($this->method, MutateCollectionMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            owner: $owner
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'mutation' => [
                'owner' => SS58Address::encode($owner),
            ],
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

        $this->assertDatabaseHas('wallets', [
            'public_key' => $owner,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_mutate_a_collection_new_owner_that_exists_locally(): void
    {
        $owner = Address::factory()->create();

        $encodedData = TransactionSerializer::encode($this->method, MutateCollectionMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            owner: $owner->public_key
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'mutation' => [
                'owner' => SS58Address::encode($owner->public_key),
            ],
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

    public function test_it_can_mutate_a_collection_with_an_empty_list_of_currencies(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, MutateCollectionMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            explicitRoyaltyCurrencies: [],
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'mutation' => [
                'explicitRoyaltyCurrencies' => [],
            ],
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

    // Exception Paths

    public function test_it_will_fail_with_no_args(): void
    {
        $response = $this->graphql($this->method, [], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of required type "BigInt!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_collection_id(): void
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

    public function test_it_will_fail_with_no_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'mutation' => [
                'owner' => Address::daemonPublicKey(),
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of required type "BigInt!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => 'invalid',
            'mutation' => [
                'owner' => Address::daemonPublicKey(),
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value "invalid"; Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_collection_id_that_doesnt_exists(): void
    {
        Collection::where('collection_chain_id', '=', $collectionId = fake()->numberBetween(2000))?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'mutation' => [
                'owner' => Address::daemonPublicKey(),
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['collectionId' => ['The selected collection id is invalid.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_mutation(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'mutation' => 'invalid',
        ], true);

        $this->assertStringContainsString(
            'Variable "$mutation" got invalid value "invalid"; Expected type "CollectionMutationInput" to be an object',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_mutation(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'mutation' => null,
        ], true);

        $this->assertStringContainsString(
            'Variable "$mutation" of non-null type "CollectionMutationInput!" must not be null',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_mutation(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
        ], true);

        $this->assertStringContainsString(
            'Variable "$mutation" of required type "CollectionMutationInput!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_negative_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => -1,
            'mutation' => [
                'owner' => Address::daemonPublicKey(),
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value -1; Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_owner_invalid(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'mutation' => [
                'owner' => 'not_substrate_address',
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['mutation.owner' => ['The mutation.owner is not a valid substrate account.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_currencies(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'mutation' => [
                'explicitRoyaltyCurrencies' => 'invalid',
            ],
        ], true);

        $this->assertStringContainsString(
            'got invalid value "invalid"; Expected type "MultiTokenIdInput" to be an object',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_more_than_ten_currencies(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'mutation' => [
                'explicitRoyaltyCurrencies' => $this->generateEncodeableTokenIds($this->generateCurrencies(11)),
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['mutation.explicitRoyaltyCurrencies' => ['The mutation.explicit royalty currencies field must not have more than 10 items.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_missing_collection_id_in_currency(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'mutation' => [
                'explicitRoyaltyCurrencies' => $this->generateEncodeableTokenIds(array_merge($this->generateCurrencies(), [
                    [
                        'tokenId' => fake()->numberBetween(),
                    ],
                ])),
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "collectionId" of required type "BigInt!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_missing_token_id_in_currency(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'mutation' => [
                'explicitRoyaltyCurrencies' => $this->generateEncodeableTokenIds(array_merge($this->generateCurrencies(), [
                    [
                        'collectionId' => $this->collection->collection_chain_id,
                    ],
                ])),
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "tokenId" of required type "EncodableTokenIdInput!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_collection_id_in_currency(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'mutation' => [
                'explicitRoyaltyCurrencies' => $this->generateEncodeableTokenIds(array_merge($this->generateCurrencies(), [
                    [
                        'collectionId' => null,
                        'tokenId' => fake()->numberBetween(),
                    ],
                ])),
            ],
        ], true);

        $this->assertStringContainsString(
            'value null at "mutation.explicitRoyaltyCurrencies[6].collectionId"; Expected non-nullable type "BigInt!" not to be null',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_token_id_in_currency(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'mutation' => [
                'explicitRoyaltyCurrencies' => $this->generateEncodeableTokenIds(array_merge($this->generateCurrencies(), [
                    [
                        'collectionId' => $this->collection->collection_chain_id,
                        'tokenId' => null,
                    ],
                ])),
            ],
        ], true);

        $this->assertStringContainsString(
            'Expected non-nullable type "EncodableTokenIdInput!" not to be null',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_duplicated_currency(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'mutation' => [
                'explicitRoyaltyCurrencies' => $this->generateEncodeableTokenIds(array_merge($currencies = $this->generateCurrencies(), [$currencies[0]])),
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['mutation.explicitRoyaltyCurrencies' => ['The mutation.explicit royalty currencies must be an array of distinct multi assets.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_collection_id_in_currency(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'mutation' => [
                'explicitRoyaltyCurrencies' => $this->generateEncodeableTokenIds(array_merge($this->generateCurrencies(), [
                    [
                        'collectionId' => 'invalid',
                        'tokenId' => fake()->numberBetween(),
                    ],
                ])),
            ],
        ], true);

        $this->assertStringContainsString(
            'Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_token_id_in_currency(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'mutation' => [
                'explicitRoyaltyCurrencies' => $this->generateEncodeableTokenIds(array_merge($this->generateCurrencies(), [
                    [
                        'collectionId' => $this->collection->collection_chain_id,
                        'tokenId' => 'invalid',
                    ],
                ])),
            ],
        ], true);

        $this->assertStringContainsString(
            'Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_negative_collection_id_in_currency(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'mutation' => [
                'explicitRoyaltyCurrencies' => $this->generateEncodeableTokenIds(array_merge($this->generateCurrencies(), [
                    [
                        'collectionId' => -1,
                        'tokenId' => fake()->numberBetween(),
                    ],
                ])),
            ],
        ], true);

        $this->assertStringContainsString(
            'Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_negative_token_id_in_currency(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'mutation' => [
                'explicitRoyaltyCurrencies' => $this->generateEncodeableTokenIds(array_merge($this->generateCurrencies(), [
                    [
                        'collectionId' => $this->collection->collection_chain_id,
                        'tokenId' => -1,
                    ],
                ])),
            ],
        ], true);

        $this->assertStringContainsString(
            'got invalid value -1 at "mutation.explicitRoyaltyCurrencies[6].tokenId.integer"; Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_overflow_collection_id_in_currency(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'mutation' => [
                'explicitRoyaltyCurrencies' => $this->generateEncodeableTokenIds(array_merge($this->generateCurrencies(), [
                    [
                        'collectionId' => Hex::MAX_UINT256,
                        'tokenId' => fake()->numberBetween(),
                    ],
                ])),
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['mutation.explicitRoyaltyCurrencies.6.collectionId' => ['The mutation.explicit royalty currencies.6.collection id is too large, the maximum value it can be is 340282366920938463463374607431768211455.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_overflow_token_id_in_currency(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'mutation' => [
                'explicitRoyaltyCurrencies' => $this->generateEncodeableTokenIds(array_merge($this->generateCurrencies(), [
                    [
                        'collectionId' => $this->collection->collection_chain_id,
                        'tokenId' => Hex::MAX_UINT256,
                    ],
                ])),
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['mutation.explicitRoyaltyCurrencies.6.tokenId.integer' => ['The mutation.explicitRoyaltyCurrencies.6.tokenId.integer is too large, the maximum value it can be is 340282366920938463463374607431768211455.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    protected function generateCurrencies(?int $total = 5): array
    {
        return array_map(
            fn () => [
                'tokenId' => ($token = Token::factory()->create())->token_chain_id,
                'collectionId' => Collection::find($token->collection_id)->collection_chain_id,
            ],
            range(0, $total),
        );
    }

    protected function generateEncodeableTokenIds($items): array
    {
        return collect($items)->transform(function ($item) {
            if (isset($item['tokenId'])) {
                $item['tokenId'] = $this->tokenIdEncoder->toEncodable($item['tokenId']);
            }

            return $item;
        })->all();
    }
}
