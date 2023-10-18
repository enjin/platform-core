<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Enums\Substrate\TokenMintCapType;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Substrate\CreateTokenParams;
use Enjin\Platform\Models\Substrate\RoyaltyPolicyParams;
use Enjin\Platform\Models\Substrate\TokenMarketBehaviorParams;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Token\Encoder;
use Enjin\Platform\Services\Token\Encoders\Integer;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Support\MocksWebsocketClient;
use Facades\Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

class CreateTokenTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;
    use MocksWebsocketClient;

    protected string $method = 'CreateToken';
    protected Codec $codec;
    protected string $defaultAccount;
    protected Model $collection;
    protected Model $recipient;
    protected Encoder $tokenIdEncoder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new Codec();
        $this->collection = Collection::factory()->create();
        $this->recipient = Wallet::factory()->create();
        $this->defaultAccount = Account::daemonPublicKey();
        $this->tokenIdEncoder = new Integer(fake()->unique()->numberBetween());
    }

    public function test_it_can_skip_validation(): void
    {
        $encodedData = $this->codec->encoder()->mint(
            $recipient = $this->recipient->public_key,
            $collectionId = random_int(1, 1000),
            new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: $initialSupply = fake()->numberBetween(1),
                cap: $capType = TokenMintCapType::SINGLE_MINT,
                unitPrice: $unitPrice = $this->randomGreaterThanMinUnitPriceFor($initialSupply)
            ),
        );

        $response = $this->graphql($this->method, [
            'recipient' => $recipient,
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
                'initialSupply' => $initialSupply,
                'unitPrice' => $unitPrice,
                'cap' => [
                    'type' => $capType->name,
                ],
            ],
            'skipValidation' => true,
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

    public function test_can_create_a_token_with_cap_equals_null_using_adapter(): void
    {
        $encodedData = $this->codec->encoder()->mint(
            $recipient = $this->recipient->public_key,
            $collectionId = $this->collection->collection_chain_id,
            $params = new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode(),
                initialSupply: $initialSupply = fake()->numberBetween(1),
                cap: TokenMintCapType::INFINITE,
                unitPrice: $this->randomGreaterThanMinUnitPriceFor($initialSupply)
            ),
        );

        $params = $params->toArray()['CreateToken'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

        $response = $this->graphql($this->method, [
            'recipient' => $recipient,
            'collectionId' => $collectionId,
            'params' => $params,
            'simulate' => null,
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
    }

    // Happy Path
    public function test_it_can_simulate(): void
    {
        $encodedData = $this->codec->encoder()->mint(
            $recipient = $this->recipient->public_key,
            $collectionId = $this->collection->collection_chain_id,
            $params = new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: $initialSupply = fake()->numberBetween(1),
                cap: TokenMintCapType::INFINITE,
                unitPrice: $this->randomGreaterThanMinUnitPriceFor($initialSupply)
            ),
        );

        $params = $params->toArray()['CreateToken'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $this->mockFee($feeDetails = app(Generator::class)->fee_details());
        $response = $this->graphql($this->method, [
            'recipient' => $recipient,
            'collectionId' => $collectionId,
            'params' => $params,
            'simulate' => true,
        ]);

        $this->assertIsNumeric($response['deposit']);
        $this->assertArraySubset([
            'id' => null,
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'fee' => $feeDetails['fakeSum'],
            'wallet' => [
                'account' => [
                    'publicKey' => $this->defaultAccount,
                ],
            ],
        ], $response);

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_can_create_a_token_with_cap_equals_null(): void
    {
        $encodedData = $this->codec->encoder()->mint(
            $recipient = $this->recipient->public_key,
            $collectionId = $this->collection->collection_chain_id,
            $params = new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: $initialSupply = fake()->numberBetween(1),
                cap: TokenMintCapType::INFINITE,
                unitPrice: $this->randomGreaterThanMinUnitPriceFor($initialSupply)
            ),
        );

        $params = $params->toArray()['CreateToken'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $response = $this->graphql($this->method, [
            'recipient' => $recipient,
            'collectionId' => $collectionId,
            'params' => $params,
            'nonce' => $nonce = fake()->numberBetween(),
        ]);

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
        $encodedData = $this->codec->encode()->mint(
            $recipient = $this->recipient->public_key,
            $collectionId = $this->collection->collection_chain_id,
            $params = new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: $initialSupply = fake()->numberBetween(1),
                cap: TokenMintCapType::INFINITE,
                unitPrice: $this->randomGreaterThanMinUnitPriceFor($initialSupply)
            ),
        );

        $params = $params->toArray()['CreateToken'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $response = $this->graphql($this->method, [
            'recipient' => $recipient,
            'collectionId' => $collectionId,
            'params' => $params,
            'signingAccount' => SS58Address::encode($signingAccount = app(Generator::class)->public_key),
        ]);

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
        $encodedData = $this->codec->encode()->mint(
            $recipient = $this->recipient->public_key,
            $collectionId = $this->collection->collection_chain_id,
            $params = new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: $initialSupply = fake()->numberBetween(1),
                cap: TokenMintCapType::INFINITE,
                unitPrice: $this->randomGreaterThanMinUnitPriceFor($initialSupply)
            ),
        );

        $params = $params->toArray()['CreateToken'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $response = $this->graphql($this->method, [
            'recipient' => $recipient,
            'collectionId' => $collectionId,
            'params' => $params,
            'signingAccount' => $signingAccount = app(Generator::class)->public_key,
        ]);

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
        $encodedData = $this->codec->encoder()->mint(
            $recipient = $this->recipient->public_key,
            $collectionId = $this->collection->collection_chain_id,
            $params = new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: fake()->numberBetween(1),
                cap: TokenMintCapType::INFINITE,
                unitPrice: null
            ),
        );

        $params = $params->toArray()['CreateToken'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $response = $this->graphql($this->method, [
            'recipient' => $recipient,
            'collectionId' => $collectionId,
            'params' => $params,
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

    public function test_can_create_a_token_with_single_mint(): void
    {
        $encodedData = $this->codec->encoder()->mint(
            $recipient = $this->recipient->public_key,
            $collectionId = $this->collection->collection_chain_id,
            new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: $initialSupply = fake()->numberBetween(1),
                cap: $capType = TokenMintCapType::SINGLE_MINT,
                unitPrice: $unitPrice = $this->randomGreaterThanMinUnitPriceFor($initialSupply)
            ),
        );

        $response = $this->graphql($this->method, [
            'recipient' => $recipient,
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
                'initialSupply' => $initialSupply,
                'unitPrice' => $unitPrice,
                'cap' => [
                    'type' => $capType->name,
                ],
            ],
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

    public function test_can_create_a_token_with_supply_cap(): void
    {
        $encodedData = $this->codec->encoder()->mint(
            $recipient = $this->recipient->public_key,
            $collectionId = $this->collection->collection_chain_id,
            new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: $initialSupply = fake()->numberBetween(1),
                cap: $capType = TokenMintCapType::SUPPLY,
                unitPrice: $unitPrice = $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                supply: $capSupply = fake()->numberBetween($initialSupply)
            ),
        );

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($recipient),
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
                'initialSupply' => $initialSupply,
                'unitPrice' => $unitPrice,
                'cap' => [
                    'type' => $capType->name,
                    'amount' => $capSupply,
                ],
            ],
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

    public function test_can_create_a_token_with_royalty_equals_null(): void
    {
        $encodedData = $this->codec->encoder()->mint(
            $recipient = $this->recipient->public_key,
            $collectionId = $this->collection->collection_chain_id,
            new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: $initialSupply = fake()->numberBetween(1),
                cap: TokenMintCapType::INFINITE,
                unitPrice: $unitPrice = $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                behavior: null,
            ),
        );

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($recipient),
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
                'initialSupply' => $initialSupply,
                'unitPrice' => $unitPrice,
                'behavior' => null,
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
            ],
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

    public function test_can_create_a_token_with_royalty(): void
    {
        $encodedData = $this->codec->encoder()->mint(
            $recipient = $this->recipient->public_key,
            $collectionId = $this->collection->collection_chain_id,
            new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: $initialSupply = fake()->numberBetween(1),
                cap: TokenMintCapType::INFINITE,
                unitPrice: $unitPrice = $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                behavior: new TokenMarketBehaviorParams(
                    hasRoyalty: new RoyaltyPolicyParams(
                        beneficiary: $beneficiary = $this->defaultAccount,
                        percentage: $percentage = fake()->numberBetween(1, 50)
                    ),
                ),
            ),
        );

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($recipient),
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
                'initialSupply' => $initialSupply,
                'unitPrice' => $unitPrice,
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
                'behavior' => [
                    'hasRoyalty' => [
                        'beneficiary' => SS58Address::encode($beneficiary),
                        'percentage' => $percentage,
                    ],
                ],
            ],
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

    public function test_can_create_a_token_with_listing_forbidden_equals_null(): void
    {
        $encodedData = $this->codec->encoder()->mint(
            $recipient = $this->recipient->public_key,
            $collectionId = $this->collection->collection_chain_id,
            new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: $initialSupply = fake()->numberBetween(1),
                cap: TokenMintCapType::INFINITE,
                unitPrice: $unitPrice = $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                listingForbidden: null,
            ),
        );

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($recipient),
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
                'initialSupply' => $initialSupply,
                'unitPrice' => $unitPrice,
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
                'listingForbidden' => null,
            ],
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

    public function test_can_create_a_token_with_listing_forbidden(): void
    {
        $encodedData = $this->codec->encoder()->mint(
            $recipient = $this->recipient->public_key,
            $collectionId = $this->collection->collection_chain_id,
            new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: $initialSupply = fake()->numberBetween(1),
                cap: TokenMintCapType::INFINITE,
                unitPrice: $unitPrice = $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                listingForbidden: $listingForbidden = fake()->boolean(),
            ),
        );

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($recipient),
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
                'initialSupply' => $initialSupply,
                'unitPrice' => $unitPrice,
                'listingForbidden' => $listingForbidden,
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
            ],
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

    public function test_can_create_a_token_with_different_types_for_numbers(): void
    {
        $encodedData = $this->codec->encoder()->mint(
            $recipient = $this->recipient->public_key,
            $collectionId = $this->collection->collection_chain_id,
            new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: $initialSupply = fake()->numberBetween(1),
                cap: TokenMintCapType::INFINITE,
                unitPrice: $unitPrice = $this->randomGreaterThanMinUnitPriceFor($initialSupply),
            ),
        );

        $response = $this->graphql($this->method, [
            'recipient' => $recipient,
            'collectionId' => (int) $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable((string) $tokenId),
                'initialSupply' => (string) $initialSupply,
                'unitPrice' => $unitPrice,
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
            ],
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

    public function test_can_create_a_token_with_bigint_collection_id(): void
    {
        $collection = Collection::factory([
            'collection_chain_id' => Hex::MAX_UINT128,
        ])->create();

        $encodedData = $this->codec->encoder()->mint(
            $recipient = $this->recipient->public_key,
            $collectionId = $collection->collection_chain_id,
            $params = new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: $initialSupply = fake()->numberBetween(1),
                cap: TokenMintCapType::INFINITE,
                unitPrice: $this->randomGreaterThanMinUnitPriceFor($initialSupply),
            ),
        );

        $params = $params->toArray()['CreateToken'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $response = $this->graphql('CreateToken', [
            'recipient' => SS58Address::encode($recipient),
            'collectionId' => $collectionId,
            'params' => $params,
        ]);

        $this->assertArraySubset([
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
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_can_create_a_token_with_bigint_token_id(): void
    {
        $collection = Collection::factory()->create();

        $encodedData = $this->codec->encoder()->mint(
            $recipient = $this->recipient->public_key,
            $collectionId = $collection->collection_chain_id,
            $params = new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode(Hex::MAX_UINT128),
                initialSupply: $initialSupply = fake()->numberBetween(1),
                cap: TokenMintCapType::INFINITE,
                unitPrice: $this->randomGreaterThanMinUnitPriceFor($initialSupply),
            ),
        );

        $params = $params->toArray()['CreateToken'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable(Hex::MAX_UINT128);

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($recipient),
            'collectionId' => $collectionId,
            'params' => $params,
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

    public function test_can_create_a_token_with_not_existent_recipient_and_creates_it(): void
    {
        Wallet::where('public_key', '=', $recipient = app(Generator::class)->public_key())?->delete();

        $collection = Collection::factory([
            'collection_chain_id' => Hex::MAX_UINT128,
        ])->create();

        $encodedData = $this->codec->encoder()->mint(
            $recipient,
            $collectionId = $collection->collection_chain_id,
            $params = new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->numberBetween()),
                initialSupply: $initialSupply = fake()->numberBetween(1),
                cap: TokenMintCapType::INFINITE,
                unitPrice: $this->randomGreaterThanMinUnitPriceFor($initialSupply),
            ),
        );

        $params = $params->toArray()['CreateToken'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($recipient),
            'collectionId' => $collectionId,
            'params' => $params,
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

        $this > $this->assertDatabaseHas('wallets', [
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
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
            ],
        ], true);

        $this->assertArraySubset(
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
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
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
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
            ],
        ], true);

        $this->assertArraySubset(
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
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
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
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
            ],
        ], true);

        $this->assertArraySubset(
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

    public function test_it_will_fail_with_supply_zero(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => 0,
                'unitPrice' => gmp_strval(gmp_pow(10, 17)),
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'The params.initial supply is too small, the minimum value it can be is 1.',
            $response['error']['params.initialSupply'][0]
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
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'The params.initial supply is too large, the maximum value it can be is',
            $response['error']['params.initialSupply'][0]
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_unit_price(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->minUnitPriceFor($initialSupply) - 1,
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['params.unitPrice' => ['The params.unit price is too small, the min token deposit is 0.01 EFI thus initialSupply * unitPrice must be greater than 10^16.']],
            $response['error']
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
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
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
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                'cap' => [],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "type" of required type "TokenMintCapType!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_cap_single_mint_and_supply(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                'cap' => [
                    'type' => TokenMintCapType::SINGLE_MINT->name,
                    'amount' => fake()->numberBetween($initialSupply),
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['params.cap.amount' => ['The params.cap.amount field is prohibited when params.cap.type is SINGLE_MINT.']],
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
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
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
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
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
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
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
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                'cap' => [
                    'type' => TokenMintCapType::SUPPLY->name,
                    'amount' => null,
                ],
            ],
        ], true);

        $this->assertArraySubset(
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
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
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
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
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
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
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
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
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
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
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
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
                'behavior' => ['hasRoyalty' => [
                    'beneficiary' => 'invalid',
                    'percentage' => fake()->numberBetween(1, 50),
                ]],
            ],
        ], true);

        $this->assertArraySubset(
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
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
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
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
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
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
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
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
                'behavior' => ['hasRoyalty' => [
                    'beneficiary' => SS58Address::encode($this->recipient->public_key),
                    'percentage' => -0.1,
                ]],
            ],
        ], true);

        $this->assertArraySubset(
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
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
                'behavior' => [
                    'hasRoyalty' => [
                        'beneficiary' => SS58Address::encode($this->recipient->public_key),
                        'percentage' => 0,
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
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
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
                'behavior' => [
                    'hasRoyalty' => [
                        'beneficiary' => SS58Address::encode($this->recipient->public_key),
                        'percentage' => 0.09,
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
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
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
                'behavior' => [
                    'hasRoyalty' => [
                        'beneficiary' => SS58Address::encode($this->recipient->public_key),
                        'percentage' => 50.1,
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
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
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
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
                'initialSupply' => $initialSupply = fake()->numberBetween(1),
                'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($initialSupply),
                'cap' => [
                    'type' => TokenMintCapType::INFINITE->name,
                ],
            ],
        ], true);

        $this->assertArraySubset(
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
