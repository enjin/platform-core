<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Enums\Substrate\TokenMintCapType;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\BatchMintMutation;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\CollectionAccount;
use Enjin\Platform\Models\Substrate\CreateTokenParams;
use Enjin\Platform\Models\Substrate\MintParams;
use Enjin\Platform\Models\Substrate\RoyaltyPolicyParams;
use Enjin\Platform\Models\Substrate\TokenMarketBehaviorParams;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Models\TokenAccount;
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
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;

class BatchMintTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;
    use MocksWebsocketClient;

    protected string $method = 'BatchMint';
    protected Codec $codec;
    protected Model $wallet;
    protected Model $collection;
    protected Model $collectionAccount;
    protected Model $token;
    protected Encoder $tokenIdEncoder;
    protected Model $tokenAccount;
    protected Model $recipient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new Codec();
        $this->recipient = Wallet::factory()->create();
        $this->collection = Collection::factory([
            'owner_wallet_id' => $this->wallet = Account::daemon(),
            'max_token_supply' => null,
            'max_token_count' => 100,
            'force_single_mint' => false,
        ])->create();
        $this->collectionAccount = CollectionAccount::factory([
            'collection_id' => $this->collection,
            'wallet_id' => $this->wallet,
            'account_count' => 1,
        ])->create();
        $this->token = Token::factory([
            'collection_id' => $this->collection,
        ])->create();
        $this->tokenIdEncoder = new Integer($this->token->token_chain_id);
        $this->tokenAccount = TokenAccount::factory([
            'collection_id' => $this->collection,
            'token_id' => $this->token,
            'wallet_id' => $this->wallet,
        ])->create();
    }

    // Happy Path
    public function test_it_can_skip_validation(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, BatchMintMutation::getEncodableParams(
            collectionId: $collectionId = random_int(1, 1000),
            recipients: [
                [
                    'accountId' => $recipient = $this->recipient->public_key,
                    'params' => $createParams = new CreateTokenParams(
                        tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->unique()->numberBetween()),
                        initialSupply: $supply = fake()->numberBetween(1),
                        cap: TokenMintCapType::INFINITE,
                        unitPrice: $this->randomGreaterThanMinUnitPriceFor($supply),
                    ),
                ],
            ]
        ));

        $params = $createParams->toArray()['CreateToken'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient),
                    'createParams' => $params,
                ],
            ],
            'skipValidation' => true,
        ]);

        $this->assertArraySubset([
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

    public function test_it_can_batch_mint_create_single_token_with_cap_null_using_adapter(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, BatchMintMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: [
                [
                    'accountId' => $recipient = $this->recipient->public_key,
                    'params' => $createParams = new CreateTokenParams(
                        tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->unique()->numberBetween()),
                        initialSupply: $supply = fake()->numberBetween(1),
                        cap: TokenMintCapType::INFINITE,
                        unitPrice: $this->randomGreaterThanMinUnitPriceFor($supply),
                    ),
                ],
            ]
        ));

        $params = $createParams->toArray()['CreateToken'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient),
                    'createParams' => $params,
                ],
            ],
            'simulate' => null,
        ]);

        $this->assertArraySubset([
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

    public function test_it_can_simulate(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, BatchMintMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: [
                [
                    'accountId' => $recipient = $this->recipient->public_key,
                    'params' => $createParams = new CreateTokenParams(
                        tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->unique()->numberBetween()),
                        initialSupply: $supply = fake()->numberBetween(1),
                        cap: TokenMintCapType::INFINITE,
                        unitPrice: $this->randomGreaterThanMinUnitPriceFor($supply),
                    ),
                ],
            ]
        ));

        $params = $createParams->toArray()['CreateToken'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $this->mockFee($feeDetails = app(Generator::class)->fee_details());
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient),
                    'createParams' => $params,
                ],
            ],
            'simulate' => true,
        ]);

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

    public function test_it_can_batch_mint_create_single_token_with_cap_null(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, BatchMintMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: [
                [
                    'accountId' => $recipient = $this->recipient->public_key,
                    'params' => $createParams = new CreateTokenParams(
                        tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->unique()->numberBetween()),
                        initialSupply: $supply = fake()->numberBetween(1),
                        cap: TokenMintCapType::INFINITE,
                        unitPrice: $this->randomGreaterThanMinUnitPriceFor($supply),
                    ),
                ],
            ]
        ));

        $params = $createParams->toArray()['CreateToken'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient),
                    'createParams' => $params,
                ],
            ],
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

    public function test_it_can_batch_mint_create_single_token_with_ss58_signing_account(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, BatchMintMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: [
                [
                    'accountId' => $recipient = $this->recipient->public_key,
                    'params' => $createParams = new CreateTokenParams(
                        tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->unique()->numberBetween()),
                        initialSupply: $supply = fake()->numberBetween(1),
                        cap: TokenMintCapType::INFINITE,
                        unitPrice: $this->randomGreaterThanMinUnitPriceFor($supply),
                    ),
                ],
            ]
        ));

        $params = $createParams->toArray()['CreateToken'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $newOwner = Wallet::factory()->create([
            'public_key' => $signingAccount = app(Generator::class)->public_key(),
        ]);
        $this->collection->update(['owner_wallet_id' => $newOwner->id]);
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient),
                    'createParams' => $params,
                ],
            ],
            'signingAccount' => SS58Address::encode($signingAccount),
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
        $this->collection->update(['owner_wallet_id' => $this->wallet->id]);
    }

    public function test_it_can_batch_mint_create_single_token_with_public_key_signing_account(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, BatchMintMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: [
                [
                    'accountId' => $recipient = $this->recipient->public_key,
                    'params' => $createParams = new CreateTokenParams(
                        tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->unique()->numberBetween()),
                        initialSupply: $supply = fake()->numberBetween(1),
                        cap: TokenMintCapType::INFINITE,
                        unitPrice: $this->randomGreaterThanMinUnitPriceFor($supply),
                    ),
                ],
            ]
        ));

        $params = $createParams->toArray()['CreateToken'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $newOwner = Wallet::factory()->create([
            'public_key' => $signingAccount = app(Generator::class)->public_key(),
        ]);
        $this->collection->update(['owner_wallet_id' => $newOwner->id]);
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient),
                    'createParams' => $params,
                ],
            ],
            'signingAccount' => $signingAccount,
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
        $this->collection->update(['owner_wallet_id' => $this->wallet->id]);
    }

    public function test_it_can_batch_mint_create_single_token_with_single_mint_cap(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, BatchMintMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: [
                [
                    'accountId' => $recipient = $this->recipient->public_key,
                    'params' => $createParams = new CreateTokenParams(
                        tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->unique()->numberBetween()),
                        initialSupply: $supply = fake()->numberBetween(1),
                        cap: TokenMintCapType::SINGLE_MINT,
                        unitPrice: $this->randomGreaterThanMinUnitPriceFor($supply),
                    ),
                ],
            ]
        ));

        $params = $createParams->toArray()['CreateToken'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient),
                    'createParams' => $params,
                ],
            ],
        ]);

        $this->assertArraySubset([
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

    public function test_it_can_batch_mint_create_single_token_with_supply_cap(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, BatchMintMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: [
                [
                    'accountId' => $recipient = $this->recipient->public_key,
                    'params' => $createParams = new CreateTokenParams(
                        tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->unique()->numberBetween()),
                        initialSupply: $supply = fake()->numberBetween(1),
                        cap: TokenMintCapType::SUPPLY,
                        unitPrice: $this->randomGreaterThanMinUnitPriceFor($supply),
                        supply: fake()->numberBetween($supply)
                    ),
                ],
            ]
        ));

        $params = $createParams->toArray()['CreateToken'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient),
                    'createParams' => $params,
                ],
            ],
        ]);

        $this->assertArraySubset([
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

    public function test_it_can_batch_mint_create_single_token_with_bigint_token_id(): void
    {
        Token::where('token_chain_id', '=', $tokenId = Hex::MAX_UINT128)?->delete();

        $encodedData = TransactionSerializer::encode($this->method, BatchMintMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: [
                [
                    'accountId' => $recipient = $this->recipient->public_key,
                    'params' => $createParams = new CreateTokenParams(
                        tokenId: $this->tokenIdEncoder->encode($tokenId),
                        initialSupply: $supply = fake()->numberBetween(1),
                        cap: TokenMintCapType::INFINITE,
                        unitPrice: $this->randomGreaterThanMinUnitPriceFor($supply),
                    ),
                ],
            ]
        ));

        $params = $createParams->toArray()['CreateToken'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient),
                    'createParams' => $params,
                ],
            ],
        ]);

        $this->assertArraySubset([
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

    public function test_it_can_batch_mint_create_single_token_with_bigint_collection_id(): void
    {
        Collection::where('collection_chain_id', '=', $collectionId = Hex::MAX_UINT128)?->delete();
        Collection::factory([
            'collection_chain_id' => $collectionId,
            'owner_wallet_id' => $this->wallet,
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, BatchMintMutation::getEncodableParams(
            collectionId: $collectionId,
            recipients: [
                [
                    'accountId' => $recipient = $this->recipient->public_key,
                    'params' => $createParams = new CreateTokenParams(
                        tokenId: $this->tokenIdEncoder->encode($tokenId = fake()->unique()->numberBetween()),
                        initialSupply: $supply = fake()->numberBetween(1),
                        cap: TokenMintCapType::INFINITE,
                        unitPrice: $this->randomGreaterThanMinUnitPriceFor($supply),
                    ),
                ],
            ]
        ));

        $params = $createParams->toArray()['CreateToken'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient),
                    'createParams' => $params,
                ],
            ],
        ]);

        $this->assertArraySubset([
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

    public function test_it_can_batch_mint_mint_single_token_without_unit_price(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, BatchMintMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: [
                [
                    'accountId' => $recipient = $this->recipient->public_key,
                    'params' => $mintParams = new MintParams(
                        tokenId: $this->tokenIdEncoder->encode(),
                        amount: fake()->numberBetween(1),
                    ),
                ],
            ]
        ));

        $params = $mintParams->toArray()['Mint'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient),
                    'mintParams' => $params,
                ],
            ],
        ]);

        $this->assertArraySubset([
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

    public function test_it_can_batch_mint_mint_single_token_with_unit_price(): void
    {
        // TODO: Need to calculate the unitPrice with the previous minted token.
        // Will do that later

        $encodedData = TransactionSerializer::encode($this->method, BatchMintMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: [
                [
                    'accountId' => $recipient = $this->recipient->public_key,
                    'params' => $mintParams = new MintParams(
                        tokenId: $this->tokenIdEncoder->encode(),
                        amount: $amount = fake()->numberBetween(1),
                        unitPrice: $this->randomGreaterThanMinUnitPriceFor($amount),
                    ),
                ],
            ]
        ));

        $params = $mintParams->toArray()['Mint'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient),
                    'mintParams' => $params,
                ],
            ],
        ]);

        $this->assertArraySubset([
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

    public function test_it_can_batch_mint_mint_single_token_with_big_int_collection_id(): void
    {
        Collection::where('collection_chain_id', '=', $collectionId = Hex::MAX_UINT128)?->delete();
        $collection = Collection::factory([
            'collection_chain_id' => $collectionId,
            'owner_wallet_id' => $this->wallet,
        ])->create();
        $token = Token::factory([
            'collection_id' => $collection,
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, BatchMintMutation::getEncodableParams(
            collectionId: $collectionId,
            recipients: [
                [
                    'accountId' => $recipient = $this->recipient->public_key,
                    'params' => $mintParams = new MintParams(
                        tokenId: $this->tokenIdEncoder->encode($tokenId = $token->token_chain_id),
                        amount: fake()->numberBetween(1),
                    ),
                ],
            ]
        ));

        $params = $mintParams->toArray()['Mint'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient),
                    'mintParams' => $params,
                ],
            ],
        ]);

        $this->assertArraySubset([
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

    public function test_it_can_batch_mint_mint_single_token_with_bigint_token_id(): void
    {
        Token::where('token_chain_id', '=', $tokenId = Hex::MAX_UINT128)?->delete();
        Token::factory([
            'collection_id' => $this->collection->id,
            'token_chain_id' => $tokenId,
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, BatchMintMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: [
                [
                    'accountId' => $recipient = $this->recipient->public_key,
                    'params' => $mintParams = new MintParams(
                        tokenId: $this->tokenIdEncoder->encode($tokenId),
                        amount: fake()->numberBetween(1),
                    ),
                ],
            ]
        ));

        $params = $mintParams->toArray()['Mint'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient),
                    'mintParams' => $params,
                ],
            ],
        ]);

        $this->assertArraySubset([
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

    public function test_it_can_batch_mint_create_multiple_tokens(): void
    {
        Token::where('collection_id', '=', $this->collection->collection_id)?->delete();

        $tokenId = fake()->numberBetween();

        $recipients = collect(range(0, 9))
            ->map(fn () => [
                'accountId' => Wallet::factory()->create()->public_key,
                'params' => new CreateTokenParams(
                    tokenId: $tokenId = $this->tokenIdEncoder->encode($tokenId),
                    initialSupply: $supply = fake()->numberBetween(1),
                    cap: TokenMintCapType::INFINITE,
                    unitPrice: $this->randomGreaterThanMinUnitPriceFor($supply),
                ),
            ]);

        $encodedData = TransactionSerializer::encode($this->method, BatchMintMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: $recipients->toArray()
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => $recipients->map(function ($recipient) use ($tokenId) {
                $params = $recipient['params']->toArray()['CreateToken'];
                $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

                return [
                    'account' => SS58Address::encode($recipient['accountId']),
                    'createParams' => $params,
                ];
            })->toArray(),
        ]);

        $this->assertArraySubset([
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

    public function test_it_can_batch_mint_mint_multiple_tokens(): void
    {
        Token::where('collection_id', '=', $this->collection->collection_id)?->delete();

        $tokens = Token::factory([
            'collection_id' => $this->collection,
            'cap' => TokenMintCapType::INFINITE->name,
        ])->count(10)->create();

        $recipients = $tokens->map(fn ($token) => [
            'accountId' => Wallet::factory()->create()->public_key,
            'params' => new MintParams(
                tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
                amount: fake()->numberBetween(1),
            ),
        ]);

        $encodedData = TransactionSerializer::encode($this->method, BatchMintMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: $recipients->toArray()
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => $recipients->map(function ($recipient) {
                $params = $recipient['params']->toArray()['Mint'];
                $params['tokenId'] = $this->tokenIdEncoder->toEncodable($params['tokenId']);

                return [
                    'account' => SS58Address::encode($recipient['accountId']),
                    'mintParams' => $params,
                ];
            })->toArray(),
        ]);

        $this->assertArraySubset([
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

    public function test_it_can_batch_mint_mixed_tokens(): void
    {
        Token::where('collection_id', '=', $this->collection->collection_id)?->delete();

        $recipients = collect(range(0, 8))
            ->map(fn ($x) => [
                'accountId' => Wallet::factory()->create()->public_key,
                'params' => new CreateTokenParams(
                    tokenId: $x + 1,
                    initialSupply: $supply = fake()->numberBetween(1),
                    cap: TokenMintCapType::INFINITE,
                    unitPrice: $this->randomGreaterThanMinUnitPriceFor($supply),
                ),
            ]);

        $tokens = Token::factory()->count(10)->sequence(fn ($s) => [
            'collection_id' => $this->collection,
            'token_chain_id' => $s->index + 10,
        ])->create();

        $recipients = $recipients->merge(
            $tokens->map(fn ($token) => [
                'accountId' => Wallet::factory()->create()->public_key,
                'params' => new MintParams(
                    tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
                    amount: fake()->numberBetween(1),
                ),
            ])
        );

        $encodedData = TransactionSerializer::encode($this->method, BatchMintMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: $recipients->toArray(),
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => $recipients->map(function ($recipient) {
                $createParams = Arr::get($recipient['params']->toArray(), 'CreateToken');
                $mintParams = Arr::get($recipient['params']->toArray(), 'Mint');

                if (isset($createParams['tokenId'])) {
                    $createParams['tokenId'] = $this->tokenIdEncoder->toEncodable($createParams['tokenId']);
                }
                if (isset($mintParams['tokenId'])) {
                    $mintParams['tokenId'] = $this->tokenIdEncoder->toEncodable($mintParams['tokenId']);
                }

                return [
                    'account' => SS58Address::encode($recipient['accountId']),
                    'createParams' => $createParams,
                    'mintParams' => $mintParams,
                ];
            })->toArray(),
        ]);

        $this->assertArraySubset([
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

    public function test_it_can_batch_mint_create_single_token_to_recipient_that_doesnt_exists(): void
    {
        Wallet::where('public_key', '=', $address = app(Generator::class)->public_key())?->delete();
        Token::where('collection_id', '=', $this->collection->collection_id)?->delete();

        $tokenId = fake()->unique()->numberBetween();

        $recipient = [
            'accountId' => $address,
            'params' => new CreateTokenParams(
                tokenId: $this->tokenIdEncoder->encode($tokenId),
                initialSupply: $supply = fake()->numberBetween(1),
                cap: TokenMintCapType::INFINITE,
                unitPrice: $this->randomGreaterThanMinUnitPriceFor($supply),
            ),
        ];

        $encodedData = TransactionSerializer::encode($this->method, BatchMintMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: [$recipient]
        ));

        $params = Arr::get($recipient['params']->toArray(), 'CreateToken');
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient['accountId']),
                    'createParams' => $params,
                ],
            ],
        ]);

        $this->assertArraySubset([
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
            'public_key' => $address,
        ]);
    }

    public function test_it_can_batch_mint_with_royalty(): void
    {
        $tokenId = fake()->unique()->numberBetween();

        $encodedData = TransactionSerializer::encode($this->method, BatchMintMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: [
                [
                    'accountId' => $recipient = $this->recipient->public_key,
                    'params' => $createParams = new CreateTokenParams(
                        tokenId: $this->tokenIdEncoder->encode($tokenId),
                        initialSupply: $supply = fake()->numberBetween(1),
                        cap: TokenMintCapType::INFINITE,
                        unitPrice: $unitPrice = $this->randomGreaterThanMinUnitPriceFor($supply),
                        behavior: new TokenMarketBehaviorParams(
                            hasRoyalty: new RoyaltyPolicyParams(
                                beneficiary: $beneficiary = Account::daemonPublicKey(),
                                percentage: $percentage = fake()->numberBetween(1, 50),
                            ),
                        ),
                    ),
                ],
            ]
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient),
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
                        'initialSupply' => $supply,
                        'unitPrice' => $unitPrice,
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                        'behavior' => [
                            'hasRoyalty' => [
                                'beneficiary' => $beneficiary,
                                'percentage' => $percentage,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertArraySubset([
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

    public function test_it_can_batch_mint_with_listing_forbidden(): void
    {
        $tokenId = fake()->unique()->numberBetween();

        $encodedData = TransactionSerializer::encode($this->method, BatchMintMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            recipients: [
                [
                    'accountId' => $recipient = $this->recipient->public_key,
                    'params' => $createParams = new CreateTokenParams(
                        tokenId: $this->tokenIdEncoder->encode($tokenId),
                        initialSupply: $supply = fake()->numberBetween(1),
                        cap: TokenMintCapType::INFINITE,
                        unitPrice: $this->randomGreaterThanMinUnitPriceFor($supply),
                        listingForbidden: fake()->boolean(),
                    ),
                ],
            ]
        ));

        $params = $createParams->toArray()['CreateToken'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($tokenId);

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => SS58Address::encode($recipient),
                    'createParams' => $params,
                ],
            ],
        ]);

        $this->assertArraySubset([
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

    // Exception Path

    public function test_it_will_fail_with_empty_args(): void
    {
        $response = $this->graphql($this->method, [], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of required type "BigInt!" was not provided',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->unique()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($supply),
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of required type "BigInt!" was not provided',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_collection_id_equals_null(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => null,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->unique()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($supply),
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of non-null type "BigInt!" must not be null',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => 'invalid',
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->unique()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($supply),
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value "invalid"; Cannot represent following value as uint256',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_collection_id_that_doesnt_exists(): void
    {
        Collection::where('collection_chain_id', '=', $collectionId = fake()->numberBetween(2000))?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->unique()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($supply),
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['collectionId' => ['The selected collection id is invalid.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_recipients(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" of required type "[MintRecipient!]!" was not provided',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_recipients(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => null,
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" of non-null type "[MintRecipient!]!" must not be null',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_recipients(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => 'invalid',
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" got invalid value "invalid"; Expected type "MintRecipient" to be an object',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_empty_list_of_recipients(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [],
        ], true);

        $this->assertArraySubset(
            ['recipients' => ['The recipients field must have at least 1 items.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_create_params_and_mint_params_missing(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'You need to set either create params or mint params for every recipient',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_create_params_equals_null(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => null,
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'You need to set either create params or mint params for every recipient',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_mint_params_equals_null(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'mintParams' => null,
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'You need to set either create params or mint params for every recipient',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_create_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => 'invalid',
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Expected type "CreateTokenParams" to be an object',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_empty_create_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "tokenId" of required type "EncodableTokenIdInput!" was not provided',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_mint_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'mintParams' => 'invalid',
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" got invalid value "invalid" at "recipients[0].mintParams"; Expected type "MintTokenParams" to be an object',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_empty_mint_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'mintParams' => [],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "tokenId" of required type "EncodableTokenIdInput!" was not provided',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_address_missing(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->unique()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($supply),
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "account" of required type "String!" was not provided',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_address_null(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => null,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->unique()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($supply),
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            '"recipients[0].account"; Expected non-nullable type "String!" not to be null',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_address(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => 'invalid',
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->unique()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($supply),
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['recipients.0.account' => ['The recipients.0.account is not a valid substrate account.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_token_id_missing_in_create_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($supply),
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "tokenId" of required type "EncodableTokenIdInput!" was not provided',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_token_id_null_in_create_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => null,
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($supply),
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" got invalid value null at "recipients[0].createParams.tokenId"; Expected non-nullable type "EncodableTokenIdInput!" not to be null',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_token_id_in_create_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable('invalid'),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($supply),
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" got invalid value "invalid" at "recipients[0].createParams.tokenId.integer"; Cannot represent following value as uint256',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_overflow_token_id_in_create_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(Hex::MAX_UINT256),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($supply),
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['integer' => ['The integer is too large, the maximum value it can be is 340282366920938463463374607431768211455.']],
            $response['errors'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_negative_token_id_in_create_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(-1),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($supply),
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" got invalid value -1 at "recipients[0].createParams.tokenId.integer"; Cannot represent following value as uint256',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_negative_initial_supply_in_create_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->unique()->numberBetween()),
                        'initialSupply' => -1,
                        'unitPrice' => $this->randomGreaterThanMinUnitPriceFor(1),
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" got invalid value -1 at "recipients[0].createParams.initialSupply"; Cannot represent following value as uint256',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_zero_initial_supply_in_create_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->unique()->numberBetween()),
                        'initialSupply' => 0,
                        'unitPrice' => $this->randomGreaterThanMinUnitPriceFor(1),
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['recipients.0.createParams.initialSupply' => ['The recipients.0.create params.initial supply is too small, the minimum value it can be is 1.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_overflow_initial_supply_in_create_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->unique()->numberBetween()),
                        'initialSupply' => Hex::MAX_UINT256,
                        'unitPrice' => $this->randomGreaterThanMinUnitPriceFor(1),
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['recipients.0.createParams.initialSupply' => ['The recipients.0.create params.initial supply is too large, the maximum value it can be is 340282366920938463463374607431768211455.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_negative_unit_price_in_create_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->unique()->numberBetween()),
                        'initialSupply' => fake()->numberBetween(1),
                        'unitPrice' => -1,
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" got invalid value -1 at "recipients[0].createParams.unitPrice"; Cannot represent following value as uint256',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_zero_unit_price_in_create_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->unique()->numberBetween()),
                        'initialSupply' => fake()->numberBetween(1),
                        'unitPrice' => 0,
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['recipients.0.createParams.unitPrice' => ['The recipients.0.create params.unit price is too small, the min token deposit is 0.01 EFI thus initialSupply * unitPrice must be greater than 10^16.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_unit_price_in_create_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->unique()->numberBetween()),
                        'initialSupply' => fake()->numberBetween(1),
                        'unitPrice' => 'invalid',
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" got invalid value "invalid" at "recipients[0].createParams.unitPrice"; Cannot represent following value as uint256',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_unit_price_less_than_the_minimum_amount_in_create_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->unique()->numberBetween()),
                        'initialSupply' => fake()->numberBetween(1, 10 ** 5),
                        'unitPrice' => 1,
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['recipients.0.createParams.unitPrice' => ['The recipients.0.create params.unit price is too small, the min token deposit is 0.01 EFI thus initialSupply * unitPrice must be greater than 10^16.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_cap_in_create_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->unique()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($supply),
                        'cap' => 'invalid',
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" got invalid value "invalid" at "recipients[0].createParams.cap"; Expected type "TokenMintCap" to be an object',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_cap_type_in_create_token_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->unique()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($supply),
                        'cap' => [
                            'type' => 'invalid',
                        ],
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Value "invalid" does not exist in "TokenMintCapType" enum',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_missing_cap_type_in_create_token_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->unique()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($supply),
                        'cap' => [
                            'amount' => $supply,
                        ],
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "type" of required type "TokenMintCapType!" was not provided',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_cap_type_equals_null_in_create_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->unique()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($supply),
                        'cap' => [
                            'type' => null,
                            'amount' => $supply,
                        ],
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" got invalid value null at "recipients[0].createParams.cap.type"; Expected non-nullable type "TokenMintCapType!" not to be null',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_amount_missing_on_cap_equals_supply_in_create_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->unique()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($supply),
                        'cap' => [
                            'type' => TokenMintCapType::SUPPLY->name,
                        ],
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Supply CAP amount must be set when using Supply CAP',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_cap_amount_lower_than_initial_supply_in_create_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->unique()->numberBetween()),
                        'initialSupply' => $supply = 2,
                        'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($supply),
                        'cap' => [
                            'type' => TokenMintCapType::SUPPLY->name,
                            'amount' => $supply - 1,
                        ],
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Supply CAP amount must be greater than or equal to initial supply',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_token_id_in_mint_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'mintParams' => [
                        'amount' => fake()->numberBetween(1),
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "tokenId" of required type "EncodableTokenIdInput!" was not provided',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_negative_token_id_in_mint_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'mintParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(-1),
                        'amount' => fake()->numberBetween(1),
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" got invalid value -1 at "recipients[0].mintParams.tokenId.integer"; Cannot represent following value as uint256',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_overflow_token_id_in_mint_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'mintParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(Hex::MAX_UINT256),
                        'amount' => fake()->numberBetween(1),
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['integer' => ['The integer is too large, the maximum value it can be is 340282366920938463463374607431768211455.']],
            $response['errors'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_token_id_in_mint_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'mintParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable('invalid'),
                        'amount' => fake()->numberBetween(1),
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" got invalid value "invalid" at "recipients[0].mintParams.tokenId.integer"; Cannot represent following value as uint256',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_token_id_in_mint_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'mintParams' => [
                        'tokenId' => null,
                        'amount' => fake()->numberBetween(1),
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Expected non-nullable type "EncodableTokenIdInput!" not to be null',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_a_token_id_that_doesnt_exists_in_mint_params(): void
    {
        Token::where('token_chain_id', '=', $tokenId = fake()->numberBetween())?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'mintParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
                        'amount' => fake()->numberBetween(1),
                    ],
                ],
            ],
        ], true);


        $this->assertArraySubset(
            ['recipients.0.mintParams.tokenId' => ['The recipients.0.mintParams.tokenId does not exist in the specified collection.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_negative_amount_in_mint_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'mintParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => -1,
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            '"recipients[0].mintParams.amount"; Cannot represent following value as uint256',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_zero_amount_in_mint_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'mintParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => 0,
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['recipients.0.mintParams.amount' => ['The recipients.0.mint params.amount is too small, the minimum value it can be is 1.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_amount_in_mint_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'mintParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => 'invalid',
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            '"recipients[0].mintParams.amount"; Cannot represent following value as uint256',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_amount_in_mint_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'mintParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "amount" of required type "BigInt!" was not provided',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_amount_in_mint_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'mintParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => null,
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            '"recipients[0].mintParams.amount"; Expected non-nullable type "BigInt!" not to be null',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_overflow_amount_in_mint_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'mintParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => Hex::MAX_UINT256,
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['recipients.0.mintParams.amount' => ['The recipients.0.mint params.amount is too large, the maximum value it can be is 340282366920938463463374607431768211455.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_unit_price_in_mint_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'mintParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => fake()->numberBetween(1),
                        'unitPrice' => 'invalid',
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'got invalid value "invalid" at "recipients[0].mintParams.unitPrice"; Cannot represent following value as uint256',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_zero_unit_price_in_mint_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'mintParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => fake()->numberBetween(1),
                        'unitPrice' => 0,
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['recipients.0.mintParams.unitPrice' => ['The recipients.0.mint params.unit price is too small, the minimum value it can be is 1.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_overflow_unit_price_in_mint_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'mintParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => fake()->numberBetween(1),
                        'unitPrice' => Hex::MAX_UINT256,
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['recipients.0.mintParams.unitPrice' => ['The recipients.0.mint params.unit price is too large, the maximum value it can be is 340282366920938463463374607431768211455.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_if_providing_mint_and_create_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'mintParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => fake()->numberBetween(1),
                    ],
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($supply),
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Cannot set create params and mint params for the same recipient',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_over_250_recipients(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => collect(range(0, 250))->map(
                fn () => [
                    'account' => $this->recipient->public_key,
                    'mintParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(),
                        'amount' => fake()->numberBetween(1),
                    ],
                ]
            )->toArray(),
        ], true);

        $this->assertArraySubset(
            ['recipients' => ['The recipients field must not have more than 250 items.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_royalty_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->minUnitPriceFor($supply),
                        'behavior' => ['hasRoyalty' => 'invalid'],
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            '"recipients[0].createParams.behavior.hasRoyalty"; Expected type "RoyaltyInput" to be an object',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_empty_array_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->minUnitPriceFor($supply),
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                        'behavior' => [
                            'hasRoyalty' => [],
                        ],
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "beneficiary" of required type "String!" was not provided',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_missing_beneficiary_in_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->minUnitPriceFor($supply),
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                        'behavior' => ['hasRoyalty' => [
                            'percentage' => fake()->numberBetween(1, 50),
                        ]],
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "beneficiary" of required type "String!" was not provided',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_beneficiary_in_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->minUnitPriceFor($supply),
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                        'behavior' => ['hasRoyalty' => [
                            'beneficiary' => null,
                            'percentage' => fake()->numberBetween(1, 50),
                        ]],
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" got invalid value null at "recipients[0].createParams.behavior.hasRoyalty.beneficiary"; Expected non-nullable type "String!" not to be null',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_beneficiary_in_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->minUnitPriceFor($supply),
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                        'behavior' => ['hasRoyalty' => [
                            'beneficiary' => 'invalid',
                            'percentage' => fake()->numberBetween(1, 50),
                        ]],
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
            [
                'recipients.0.createParams.behavior.hasRoyalty.beneficiary' => [
                    0 => 'The recipients.0.create params.behavior.has royalty.beneficiary is not a valid substrate account.',
                ],
            ],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_missing_percentage_in_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->minUnitPriceFor($supply),
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                        'behavior' => ['hasRoyalty' => [
                            'beneficiary' => $this->recipient->public_key,
                        ]],
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "percentage" of required type "Float!" was not provided',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_percentage_in_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->minUnitPriceFor($supply),
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                        'behavior' => ['hasRoyalty' => [
                            'beneficiary' => $this->recipient->public_key,
                            'percentage' => null,
                        ]],
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'got invalid value null at "recipients[0].createParams.behavior.hasRoyalty.percentage"; Expected non-nullable type "Float!" not to be null',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_percentage_in_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->minUnitPriceFor($supply),
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                        'behavior' => ['hasRoyalty' => [
                            'beneficiary' => $this->recipient->public_key,
                            'percentage' => 'invalid',
                        ]],
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Float cannot represent non numeric value',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_negative_percentage_in_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->minUnitPriceFor($supply),
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                        'behavior' => ['hasRoyalty' => [
                            'beneficiary' => $this->recipient->public_key,
                            'percentage' => -1,
                        ]],
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
            [
                'recipients.0.createParams.behavior.hasRoyalty.percentage' => [
                    0 => 'The recipients.0.create params.behavior.has royalty.percentage valid for a royalty is in the range of 0.1% to 50% and a maximum of 7 decimal places.',
                ],
            ],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_zero_percentage_in_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->minUnitPriceFor($supply),
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                        'behavior' => ['hasRoyalty' => [
                            'beneficiary' => $this->recipient->public_key,
                            'percentage' => 0,
                        ]],
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
            [
                'recipients.0.createParams.behavior.hasRoyalty.percentage' => [
                    0 => 'The recipients.0.create params.behavior.has royalty.percentage valid for a royalty is in the range of 0.1% to 50% and a maximum of 7 decimal places.',
                ],
            ],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_less_than_min_percentage_in_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->minUnitPriceFor($supply),
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                        'behavior' => ['hasRoyalty' => [
                            'beneficiary' => $this->recipient->public_key,
                            'percentage' => 0.09,
                        ]],
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
            [
                'recipients.0.createParams.behavior.hasRoyalty.percentage' => [
                    0 => 'The recipients.0.create params.behavior.has royalty.percentage valid for a royalty is in the range of 0.1% to 50% and a maximum of 7 decimal places.',
                ],
            ],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_more_than_max_percentage_in_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->minUnitPriceFor($supply),
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                        'behavior' => ['hasRoyalty' => [
                            'beneficiary' => $this->recipient->public_key,
                            'percentage' => 50.1,
                        ]],
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
            [
                'recipients.0.createParams.behavior.hasRoyalty.percentage' => [
                    0 => 'The recipients.0.create params.behavior.has royalty.percentage valid for a royalty is in the range of 0.1% to 50% and a maximum of 7 decimal places.',
                ],
            ],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_percentage_with_more_than_seven_decimal_places_in_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->minUnitPriceFor($supply),
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                        'behavior' => ['hasRoyalty' => [
                            'beneficiary' => $this->recipient->public_key,
                            'percentage' => 10.000000001,
                        ]],
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
            [
                'recipients.0.createParams.behavior.hasRoyalty.percentage' => [
                    0 => 'The recipients.0.create params.behavior.has royalty.percentage valid for a royalty is in the range of 0.1% to 50% and a maximum of 7 decimal places.',
                ],
            ],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    // Helpers

    public function test_it_will_fail_with_invalid_listing_forbidden(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->minUnitPriceFor($supply),
                        'listingForbidden' => 'invalid',
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipients" got invalid value "invalid" at "recipients[0].createParams.listingForbidden"; Boolean cannot represent a non boolean value',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_if_exceed_max_token_count_in_collection(): void
    {
        $this->collection->forceFill(['max_token_count' => 0])->save();
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipients' => [
                [
                    'account' => $this->recipient->public_key,
                    'createParams' => [
                        'tokenId' => $this->tokenIdEncoder->toEncodable(fake()->numberBetween()),
                        'initialSupply' => $supply = fake()->numberBetween(1),
                        'unitPrice' => $this->randomGreaterThanMinUnitPriceFor($supply),
                        'cap' => [
                            'type' => TokenMintCapType::INFINITE->name,
                        ],
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['collectionId' => ['The overall token count 2 have exceeded the maximum cap of 0 tokens.']],
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
