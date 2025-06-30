<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Enums\Substrate\TokenMintCapType;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\CreateTokenMutation;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Indexer\Token;
use Enjin\Platform\Models\Substrate\CreateTokenParams;
use Enjin\Platform\Models\Substrate\RoyaltyPolicyParams;
use Enjin\Platform\Models\Substrate\TokenMarketBehaviorParams;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Rules\IsCollectionOwner;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Token\Encoder;
use Enjin\Platform\Services\Token\Encoders\Integer;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Support\MocksHttpClient;
use Facades\Enjin\Platform\Facades\TransactionSerializer;
use Facades\Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Faker\Generator;
use Illuminate\Support\Facades\Event;
use Override;

class CreateTokenTest extends TestCaseGraphQL
{
    use MocksHttpClient;

    protected string $method = 'CreateToken';
    protected Codec $codec;
    protected Codec $collection;
    protected Wallet $recipient;
    protected Encoder $tokenIdEncoder;
    protected Wallet $wallet;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->codec = new Codec();
        $this->wallet = Account::daemon();
        $this->collection = Collection::factory()->create(['owner_wallet_id' => $this->wallet]);
        $this->recipient = Wallet::factory()->create();
        $this->tokenIdEncoder = new Integer(fake()->unique()->numberBetween());
    }

    public function test_it_can_skip_validation(): void
    {
        $encodedData = TransactionSerializer::encode('Mint', CreateTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->public_key,
            collectionId: $collectionId = random_int(1, 1000),
            createTokenParams: new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: $initialSupply = fake()->numberBetween(1),
                cap: $capType = TokenMintCapType::COLLAPSING_SUPPLY,
            ),
        ));

        $response = $this->graphql($this->method, [
            'recipient' => $recipient,
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
                'initialSupply' => $initialSupply,
                'cap' => [
                    'type' => $capType->name,
                ],
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

    public function test_can_create_a_token_with_cap_equals_null_using_adapter(): void
    {
        $encodedData = TransactionSerializer::encode('Mint', CreateTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->public_key,
            collectionId: $collectionId = $this->collection->collection_chain_id,
            createTokenParams: $params = new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode(),
                initialSupply: fake()->numberBetween(1),
            ),
        ));

        $params = $params->toArray()[$this->method];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

        $response = $this->graphql($this->method, [
            'recipient' => $recipient,
            'collectionId' => $collectionId,
            'params' => $params,
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
    }

    // Happy Path
    public function test_it_can_simulate(): void
    {
        $encodedData = TransactionSerializer::encode('Mint', CreateTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->public_key,
            collectionId: $collectionId = $this->collection->collection_chain_id,
            createTokenParams: $params = new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: fake()->numberBetween(1),
            ),
        ));

        $params = $params->toArray()[$this->method];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $this->mockFee($feeDetails = app(Generator::class)->fee_details());
        $response = $this->graphql($this->method, [
            'recipient' => $recipient,
            'collectionId' => $collectionId,
            'params' => $params,
            'simulate' => true,
        ]);

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

    public function test_it_can_bypass_ownership(): void
    {
        $collection = Collection::factory()->create(['owner_wallet_id' => Wallet::factory()->create()]);
        $response = $this->graphql($this->method, $params = [
            'recipient' => $this->recipient->public_key,
            'collectionId' => $collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween(1, 10)),
            ],
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

    public function test_can_create_a_token_with_cap_equals_null(): void
    {
        $encodedData = TransactionSerializer::encode('Mint', CreateTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->public_key,
            collectionId: $collectionId = $this->collection->collection_chain_id,
            createTokenParams: $params = new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: fake()->numberBetween(1),
            ),
        ));
        $params = $params->toArray()[$this->method];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $response = $this->graphql($this->method, [
            'recipient' => $recipient,
            'collectionId' => $collectionId,
            'params' => $params,
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

    public function test_can_create_a_token_with_ss58_signing_account(): void
    {
        $signingWallet = Wallet::factory([
            'public_key' => $signingAccount = app(Generator::class)->public_key(),
        ])->create();
        $collection = Collection::factory(['owner_wallet_id' => $signingWallet])->create();

        $encodedData = TransactionSerializer::encode('Mint', CreateTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->public_key,
            collectionId: $collectionId = $collection->collection_chain_id,
            createTokenParams: $params = new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: fake()->numberBetween(1),
            ),
        ));

        $params = $params->toArray()[$this->method];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $response = $this->graphql($this->method, [
            'recipient' => $recipient,
            'collectionId' => $collectionId,
            'params' => $params,
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

    public function test_can_create_a_token_with_public_key_signing_account(): void
    {
        $signingWallet = Wallet::factory([
            'public_key' => $signingAccount = app(Generator::class)->public_key(),
        ])->create();
        $collection = Collection::factory(['owner_wallet_id' => $signingWallet])->create();

        $encodedData = TransactionSerializer::encode('Mint', CreateTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->public_key,
            collectionId: $collectionId = $collection->collection_chain_id,
            createTokenParams: $params = new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: fake()->numberBetween(1),
            ),
        ));

        $params = $params->toArray()[$this->method];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $response = $this->graphql($this->method, [
            'recipient' => $recipient,
            'collectionId' => $collectionId,
            'params' => $params,
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

    public function test_can_create_a_token_with_unit_price_equals_null(): void
    {
        $encodedData = TransactionSerializer::encode('Mint', CreateTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->public_key,
            collectionId: $collectionId = $this->collection->collection_chain_id,
            createTokenParams: $params = new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: fake()->numberBetween(1),
            ),
        ));

        $params = $params->toArray()[$this->method];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $response = $this->graphql($this->method, [
            'recipient' => $recipient,
            'collectionId' => $collectionId,
            'params' => $params,
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

    public function test_can_create_a_token_with_collapsing_supply(): void
    {
        $encodedData = TransactionSerializer::encode('Mint', CreateTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->public_key,
            collectionId: $collectionId = $this->collection->collection_chain_id,
            createTokenParams: new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: $initialSupply = fake()->numberBetween(1),
                cap: $capType = TokenMintCapType::COLLAPSING_SUPPLY,
                capSupply: $initialSupply + 10
            ),
        ));

        $response = $this->graphql($this->method, [
            'recipient' => $recipient,
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
                'initialSupply' => $initialSupply,
                'cap' => [
                    'type' => $capType->name,
                    'amount' => $initialSupply + 10,
                ],
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

    public function test_can_create_a_token_with_supply_cap(): void
    {
        $encodedData = TransactionSerializer::encode(
            'Mint',
            CreateTokenMutation::getEncodableParams(
                recipientAccount: $recipient = $this->recipient->public_key,
                collectionId: $collectionId = $this->collection->collection_chain_id,
                createTokenParams: new CreateTokenParams(
                    tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                    initialSupply: $initialSupply = fake()->numberBetween(1),
                    cap: $capType = TokenMintCapType::SUPPLY,
                    capSupply: $capSupply = fake()->numberBetween($initialSupply)
                )
            ),
        );

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($recipient),
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
                'initialSupply' => $initialSupply,
                'cap' => [
                    'type' => $capType->name,
                    'amount' => $capSupply,
                ],
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

    public function test_can_create_a_token_with_royalty_equals_null(): void
    {
        $encodedData = TransactionSerializer::encode(
            'Mint',
            CreateTokenMutation::getEncodableParams(
                recipientAccount: $recipient = $this->recipient->public_key,
                collectionId: $collectionId = $this->collection->collection_chain_id,
                createTokenParams: new CreateTokenParams(
                    tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                    initialSupply: $initialSupply = fake()->numberBetween(1),
                    behavior: null,
                )
            ),
        );

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($recipient),
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
                'initialSupply' => $initialSupply,
                'behavior' => null,
                'cap' => null,
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

    public function test_can_create_a_token_with_royalty(): void
    {
        $encodedData = TransactionSerializer::encode('Mint', CreateTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->public_key,
            collectionId: $collectionId = $this->collection->collection_chain_id,
            createTokenParams: new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: $initialSupply = fake()->numberBetween(1),
                behavior: new TokenMarketBehaviorParams(
                    hasRoyalty: new RoyaltyPolicyParams(
                        beneficiary: $beneficiary = $this->wallet->public_key,
                        percentage: $percentage = fake()->numberBetween(1, 50)
                    ),
                ),
            ),
        ));

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($recipient),
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
                'initialSupply' => $initialSupply,
                'cap' => null,
                'behavior' => [
                    'hasRoyalty' => [
                        'beneficiary' => SS58Address::encode($beneficiary),
                        'percentage' => $percentage,
                    ],
                ],
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

    public function test_can_create_a_token_with_listing_forbidden_equals_null(): void
    {
        $encodedData = TransactionSerializer::encode(
            'Mint',
            CreateTokenMutation::getEncodableParams(
                recipientAccount: $recipient = $this->recipient->public_key,
                collectionId: $collectionId = $this->collection->collection_chain_id,
                createTokenParams: new CreateTokenParams(
                    tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                    initialSupply: $initialSupply = fake()->numberBetween(1),
                    listingForbidden: null,
                )
            ),
        );

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($recipient),
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
                'initialSupply' => $initialSupply,
                'cap' => null,
                'listingForbidden' => null,
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

    public function test_can_create_a_token_with_listing_forbidden(): void
    {
        $encodedData = TransactionSerializer::encode(
            'Mint',
            CreateTokenMutation::getEncodableParams(
                recipientAccount: $recipient = $this->recipient->public_key,
                collectionId: $collectionId = $this->collection->collection_chain_id,
                createTokenParams: new CreateTokenParams(
                    tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                    initialSupply: $initialSupply = fake()->numberBetween(1),
                    listingForbidden: $listingForbidden = fake()->boolean(),
                )
            ),
        );

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($recipient),
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
                'initialSupply' => $initialSupply,
                'listingForbidden' => $listingForbidden,
                'cap' => null,
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

    public function test_can_create_a_token_with_different_types_for_numbers(): void
    {
        $encodedData = TransactionSerializer::encode('Mint', CreateTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->public_key,
            collectionId: $collectionId = $this->collection->collection_chain_id,
            createTokenParams: new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: $initialSupply = fake()->numberBetween(1),
            ),
        ));

        $response = $this->graphql($this->method, [
            'recipient' => $recipient,
            'collectionId' => (int) $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable((string) $tokenId),
                'initialSupply' => (string) $initialSupply,
                'cap' => null,
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

    public function test_can_create_a_token_with_bigint_collection_id(): void
    {
        Collection::where('collection_chain_id', '=', Hex::MAX_UINT128)?->delete();

        $collection = Collection::factory([
            'owner_wallet_id' => $this->wallet->id,
            'collection_chain_id' => Hex::MAX_UINT128,
        ])->create();

        $encodedData = TransactionSerializer::encode('Mint', CreateTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->public_key,
            collectionId: $collectionId = $collection->collection_chain_id,
            createTokenParams: $params = new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: fake()->numberBetween(1),
            ),
        ));

        $params = $params->toArray()[$this->method];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($recipient),
            'collectionId' => $collectionId,
            'params' => $params,
        ]);

        $this->assertArrayContainsArray([
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_can_create_a_token_with_bigint_token_id(): void
    {
        $collection = Collection::factory()->create(['owner_wallet_id' => $this->wallet]);

        $encodedData = TransactionSerializer::encode('Mint', CreateTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->public_key,
            collectionId: $collectionId = $collection->collection_chain_id,
            createTokenParams: $params = new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode(Hex::MAX_UINT128),
                initialSupply: fake()->numberBetween(1),
            ),
        ));

        $params = $params->toArray()[$this->method];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable(Hex::MAX_UINT128);

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($recipient),
            'collectionId' => $collectionId,
            'params' => $params,
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

    public function test_can_create_a_token_with_not_existent_recipient_and_creates_it(): void
    {
        Wallet::where('public_key', '=', $recipient = app(Generator::class)->public_key())?->delete();
        Collection::where('collection_chain_id', '=', Hex::MAX_UINT128)?->delete();

        $collection = Collection::factory([
            'owner_wallet_id' => $this->wallet,
            'collection_chain_id' => Hex::MAX_UINT128,
        ])->create();

        $encodedData = TransactionSerializer::encode('Mint', CreateTokenMutation::getEncodableParams(
            recipientAccount: $recipient,
            collectionId: $collectionId = $collection->collection_chain_id,
            createTokenParams: $params = new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: fake()->numberBetween(1),
            ),
        ));

        $params = $params->toArray()[$this->method];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($recipient),
            'collectionId' => $collectionId,
            'params' => $params,
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
            'public_key' => $recipient,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    // Exceptions Path

    public function test_it_will_fail_trying_to_create_a_token_with_an_id_that_already_exists(): void
    {
        Token::where('token_chain_id', '=', $tokenId = fake()->numberBetween())?->delete();
        Token::factory([
            'collection_id' => $this->collection->id,
            'token_chain_id' => $tokenId,
        ])->create();

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
                'initialSupply' => fake()->numberBetween(1),
                'cap' => null,
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['params.tokenId' => ['The params.token id already exists in the specified collection.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_recipient(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => 'not_substrate_address',
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => fake()->numberBetween(1),
                'cap' => null,
            ],
        ], true);

        $this->assertStringContainsString(
            'The recipient is not a valid substrate account.',
            $response['error']['recipient'][0]
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_collection_id_doesnt_exists(): void
    {
        Collection::where('collection_chain_id', '=', $collectionId = fake()->numberBetween(2000))?->delete();

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => fake()->numberBetween(1),
                'cap' => null,
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['collectionId' => ['The selected collection id is invalid.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_negative_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(-1),
                'initialSupply' => fake()->numberBetween(1),

            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$params" got invalid value -1 at "params.tokenId.integer"; Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_overflow_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(Hex::MAX_UINT256),
                'initialSupply' => fake()->numberBetween(1),
                'cap' => null,
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['integer' => ['The integer is too large, the maximum value it can be is 340282366920938463463374607431768211455.']],
            $response['errors']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_negative_supply(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => -1,
                'unitPrice' => gmp_strval(gmp_pow(10, 17)),
            ],
        ], true);

        $this->assertStringContainsString(
            '"params.initialSupply"; Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_supply_overflow(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => Hex::MAX_UINT256,
                'unitPrice' => gmp_strval(gmp_pow(10, 17)),
                'cap' => null,
            ],
        ], true);

        $this->assertStringContainsString(
            'The params.initial supply is too large, the maximum value it can be is',
            $response['error']['params.initialSupply'][0]
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_cap(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => fake()->numberBetween(1),
                'cap' => 'invalid',
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$params" got invalid value "invalid" at "params.cap"; Expected type "TokenMintCap" to be an object',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_empty_cap(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => fake()->numberBetween(1),

                'cap' => [],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "type" of required type "TokenMintCapType!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_cap_supply_less_than_initial_supply(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'cap' => [
                    'type' => TokenMintCapType::SUPPLY->name,
                    'amount' => fake()->numberBetween(0, $initialSupply - 1),
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'The params.cap.amount is too small, the minimum value it can be is',
            $response['error']['params.cap.amount'][0]
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_cap_supply_amount_zero(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => fake()->numberBetween(1),

                'cap' => [
                    'type' => TokenMintCapType::SUPPLY->name,
                    'amount' => 0,
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'The params.cap.amount is too small, the minimum value it can be is',
            $response['error']['params.cap.amount'][0]
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_cap_supply_amount_negative(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => fake()->numberBetween(1),

                'cap' => [
                    'type' => TokenMintCapType::SUPPLY->name,
                    'supply' => -1,
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$params" got invalid value {"type":"SUPPLY","supply":-1} at "params.cap"',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_cap_supply_amount_null(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => fake()->numberBetween(1),

                'cap' => [
                    'type' => TokenMintCapType::SUPPLY->name,
                    'amount' => null,
                ],
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['params.cap.amount' => ['The params.cap.amount field is required when params.cap.type is SUPPLY.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_without_cap_type(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'cap' => [
                    'amount' => fake()->numberBetween($initialSupply),
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            '"params.cap"; Field "type" of required type "TokenMintCapType!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => fake()->numberBetween(1),
                'behavior' => 'invalid',
            ],
        ], true);

        $this->assertStringContainsString(
            'got invalid value "invalid" at "params.behavior"; Expected type "TokenMarketBehaviorInput" to be an object',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_empty_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => fake()->numberBetween(1),
                'cap' => null,
                'behavior' => [
                    'hasRoyalty' => [],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "beneficiary" of required type "String!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_missing_beneficiary_on_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => fake()->numberBetween(1),
                'behavior' => [
                    'hasRoyalty' => [
                        'percentage' => fake()->numberBetween(1, 50),
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "beneficiary" of required type "String!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_beneficiary_on_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => fake()->numberBetween(1),
                'behavior' => ['hasRoyalty' => [
                    'beneficiary' => null,
                    'percentage' => fake()->numberBetween(1, 50),
                ]],
            ],
        ], true);

        $this->assertStringContainsString(
            '"params.behavior.hasRoyalty.beneficiary"; Expected non-nullable type "String!" not to be null',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_beneficiary_on_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => fake()->numberBetween(1),
                'behavior' => ['hasRoyalty' => [
                    'beneficiary' => 'invalid',
                    'percentage' => fake()->numberBetween(1, 50),
                ]],
            ],
        ], true);

        $this->assertArrayContainsArray(
            [
                'params.behavior.hasRoyalty.beneficiary' => [
                    0 => 'The params.behavior.has royalty.beneficiary is not a valid substrate account.',
                ],
            ],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_percentage_on_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => fake()->numberBetween(1),
                'cap' => null,
                'behavior' => ['hasRoyalty' => [
                    'beneficiary' => SS58Address::encode($this->recipient->public_key),
                ]],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "percentage" of required type "Float!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_percentage_on_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => fake()->numberBetween(1),
                'behavior' => [
                    'hasRoyalty' => [
                        'beneficiary' => SS58Address::encode($this->recipient->public_key),
                        'percentage' => null,
                    ], ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Expected non-nullable type "Float!" not to be null',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_percentage_on_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => fake()->numberBetween(1),
                'behavior' => ['hasRoyalty' => [
                    'beneficiary' => SS58Address::encode($this->recipient->public_key),
                    'percentage' => 'invalid',
                ]],
            ],
        ], true);

        $this->assertStringContainsString(
            'Float cannot represent non numeric value',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_negative_percentage_on_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => fake()->numberBetween(1),
                'cap' => null,
                'behavior' => ['hasRoyalty' => [
                    'beneficiary' => SS58Address::encode($this->recipient->public_key),
                    'percentage' => -0.1,
                ]],
            ],
        ], true);

        $this->assertArrayContainsArray(
            [
                'params.behavior.hasRoyalty.percentage' => [
                    0 => 'The params.behavior.has royalty.percentage valid for a royalty is in the range of 0.1% to 50% and a maximum of 7 decimal places.',
                ],
            ],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_zero_percentage_on_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => fake()->numberBetween(1),
                'cap' => null,
                'behavior' => [
                    'hasRoyalty' => [
                        'beneficiary' => SS58Address::encode($this->recipient->public_key),
                        'percentage' => 0,
                    ],
                ],
            ],
        ], true);

        $this->assertArrayContainsArray(
            [
                'params.behavior.hasRoyalty.percentage' => [
                    0 => 'The params.behavior.has royalty.percentage valid for a royalty is in the range of 0.1% to 50% and a maximum of 7 decimal places.',
                ],
            ],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_less_than_the_minimum_percentage_on_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => fake()->numberBetween(1),
                'behavior' => [
                    'hasRoyalty' => [
                        'beneficiary' => SS58Address::encode($this->recipient->public_key),
                        'percentage' => 0.09,
                    ],
                ],
            ],
        ], true);

        $this->assertArrayContainsArray(
            [
                'params.behavior.hasRoyalty.percentage' => [
                    0 => 'The params.behavior.has royalty.percentage valid for a royalty is in the range of 0.1% to 50% and a maximum of 7 decimal places.',
                ],
            ],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_more_than_the_max_percentage_on_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => fake()->numberBetween(1),
                'cap' => null,
                'behavior' => [
                    'hasRoyalty' => [
                        'beneficiary' => SS58Address::encode($this->recipient->public_key),
                        'percentage' => 50.1,
                    ],
                ],
            ],
        ], true);

        $this->assertArrayContainsArray(
            [
                'params.behavior.hasRoyalty.percentage' => [
                    0 => 'The params.behavior.has royalty.percentage valid for a royalty is in the range of 0.1% to 50% and a maximum of 7 decimal places.',
                ],
            ],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_listing_forbidden(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => fake()->numberBetween(1),
                'listingForbidden' => 'invalid',
            ],
        ], true);

        $this->assertStringContainsString(
            '"params.listingForbidden"; Boolean cannot represent a non boolean value',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_if_exceed_max_token_count_in_collection(): void
    {
        $this->collection->forceFill(['max_token_count' => 0])->save();
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => fake()->numberBetween(1),
                'cap' => null,
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['collectionId' => ['The overall token count 1 have exceeded the maximum cap of 0 tokens.']],
            $response['error'],
        );
    }

    protected function randomGreaterThanMinUnitPriceFor(string $initialSupply): string
    {
        $min = $this->minUnitPriceFor($initialSupply);

        return gmp_strval(gmp_random_range($min, Hex::MAX_UINT128));
    }

    protected function minUnitPriceFor(string $initialSupply): string
    {
        return gmp_strval(gmp_div(gmp_pow(10, 16), gmp_init($initialSupply), GMP_ROUND_PLUSINF));
    }
}
