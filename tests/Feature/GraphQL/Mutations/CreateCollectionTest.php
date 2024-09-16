<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\CreateCollectionMutation;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Substrate\MintPolicyParams;
use Enjin\Platform\Models\Substrate\RoyaltyPolicyParams;
use Enjin\Platform\Models\Token;
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
use Illuminate\Support\Facades\Event;

class CreateCollectionTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;
    use MocksWebsocketClient;

    protected string $method = 'CreateCollection';

    protected Codec $codec;
    protected string $defaultAccount;
    protected Encoder $tokenIdEncoder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new Codec();
        $this->defaultAccount = Account::daemonPublicKey();
        $this->tokenIdEncoder = new Integer();
    }

    // Happy Path

    public function test_it_will_fail_with_duplicate_names(): void
    {
        self::$queries['CreateCollectionDuplicateFieldName'] = '
            mutation CreateCollection {
                CreateCollection(
                    mintPolicy: {
                        maxTokenCount: 100000
                        maxTokenSupply: 10
                        forceSingleMint: true
                    }
                    marketPolicy: {
                        royalty: {
                            beneficiary: "rf8YmxhSe9WGJZvCH8wtzAndweEmz6dTV6DjmSHgHvPEFNLAJ",
                            percentage: 5
                            percentage: 50
                        }
                    }

                ) {
                    id
                    encodedData
                    state
                }
            }';
        $response = $this->graphql('CreateCollectionDuplicateFieldName', [], true, ['operationName' => $this->method]);
        $this->assertArraySubset(
            ['percentage' => ['message' => 'There can be only one input field named "percentage".']],
            $response['errors']
        );
    }

    public function test_create_collection_single_mint(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, CreateCollectionMutation::getEncodableParams(
            mintPolicy: $policy = new MintPolicyParams(
                forceSingleMint: fake()->boolean(),
            )
        ));

        $response = $this->graphql($this->method, [
            'mintPolicy' => $policy->toArray(),
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

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_create_collection_with_ss58_signing_account(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, CreateCollectionMutation::getEncodableParams(
            mintPolicy: $policy = new MintPolicyParams(
                forceSingleMint: fake()->boolean(),
            )
        ));

        $response = $this->graphql($this->method, [
            'mintPolicy' => $policy->toArray(),
            'simulate' => null,
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

    public function test_create_collection_with_public_key_signing_account(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, CreateCollectionMutation::getEncodableParams(
            mintPolicy: $policy = new MintPolicyParams(
                forceSingleMint: fake()->boolean(),
            )
        ));

        $response = $this->graphql($this->method, [
            'mintPolicy' => $policy->toArray(),
            'simulate' => null,
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

    public function test_it_can_simulate(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, CreateCollectionMutation::getEncodableParams(
            mintPolicy: $policy = new MintPolicyParams(
                forceSingleMint: fake()->boolean(),
            )
        ));

        $this->mockFee($feeDetails = app(Generator::class)->fee_details());
        $response = $this->graphql($this->method, [
            'mintPolicy' => $policy->toArray(),
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

    public function test_one_create_collection_transaction_is_created_using_idempotency(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, CreateCollectionMutation::getEncodableParams(
            mintPolicy: $policy = new MintPolicyParams(
                forceSingleMint: fake()->boolean(),
            )
        ));

        $idempotencyKey = fake()->uuid();

        $expectedResponse = [
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
            'idempotencyKey' => $idempotencyKey,
        ];

        // First run
        $response = $this->graphql($this->method, [
            'mintPolicy' => $policy->toArray(),
            'idempotencyKey' => $idempotencyKey,
        ]);

        $this->assertArraySubset($expectedResponse, $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $responseId = $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        // Second run, should return the same data as the first run, but without dispatching a new event.
        $response = $this->graphql($this->method, [
            'mintPolicy' => $policy->toArray(),
            'idempotencyKey' => $idempotencyKey,
        ]);

        $this->assertArraySubset($expectedResponse, $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $responseId,
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatchedTimes(TransactionCreated::class, 1);
    }

    public function test_create_collection_with_max_token_count(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, CreateCollectionMutation::getEncodableParams(
            mintPolicy: $policy = new MintPolicyParams(
                forceSingleMint: fake()->boolean(),
                maxTokenCount: fake()->numberBetween(1, Hex::MAX_UINT64),
            )
        ));

        $response = $this->graphql($this->method, [
            'mintPolicy' => $policy->toArray(),
            'nonce' => $nonce = fake()->numberBetween(),
        ]);

        $this->assertArraySubset([
            'state' => TransactionState::PENDING->name,
            'method' => $this->method,
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

    public function test_create_collection_with_max_token_supply(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, CreateCollectionMutation::getEncodableParams(
            mintPolicy: $policy = new MintPolicyParams(
                forceSingleMint: fake()->boolean(),
                maxTokenSupply: fake()->numberBetween(1, Hex::MAX_UINT64),
            )
        ));

        $response = $this->graphql($this->method, [
            'mintPolicy' => $policy->toArray(),
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

    public function test_create_collection_with_all_mint_args(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, CreateCollectionMutation::getEncodableParams(
            mintPolicy: $policy = new MintPolicyParams(
                forceSingleMint: fake()->boolean(),
                maxTokenCount: fake()->numberBetween(1, Hex::MAX_UINT64),
                maxTokenSupply: fake()->numberBetween(1, Hex::MAX_UINT64),
            )
        ));

        $response = $this->graphql($this->method, [
            'mintPolicy' => $policy->toArray(),
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

    public function test_create_collection_works_with_big_int_max_token_count(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, CreateCollectionMutation::getEncodableParams(
            mintPolicy: $policy = new MintPolicyParams(
                forceSingleMint: fake()->boolean(),
                maxTokenCount: Hex::MAX_UINT64,
            )
        ));

        $response = $this->graphql($this->method, [
            'mintPolicy' => $policy->toArray(),
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

    public function test_create_collection_works_with_big_int_max_token_supply(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, CreateCollectionMutation::getEncodableParams(
            mintPolicy: $policy = new MintPolicyParams(
                forceSingleMint: fake()->boolean(),
                maxTokenSupply: Hex::MAX_UINT128,
            )
        ));

        $response = $this->graphql($this->method, [
            'mintPolicy' => $policy->toArray(),
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

    public function test_it_works_with_royalty(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, CreateCollectionMutation::getEncodableParams(
            mintPolicy: $mintPolicy = new MintPolicyParams(
                forceSingleMint: fake()->boolean(),
            ),
            marketPolicy: $marketPolicy = new RoyaltyPolicyParams(
                beneficiary: app(Generator::class)->public_key(),
                percentage: fake()->numberBetween(1, 50)
            ),
        ));

        $response = $this->graphql($this->method, [
            'mintPolicy' => $mintPolicy->toArray(),
            'marketPolicy' => [
                'royalty' => $marketPolicy->toArray(),
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

    public function test_it_works_with_explicit_royalty_currencies(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, CreateCollectionMutation::getEncodableParams(
            mintPolicy: $mintPolicy = new MintPolicyParams(
                forceSingleMint: fake()->boolean(),
            ),
            explicitRoyaltyCurrencies: $currencies = $this->generateCurrencies(fake()->numberBetween(1, 9)),
        ));

        $response = $this->graphql($this->method, [
            'mintPolicy' => $mintPolicy->toArray(),
            'explicitRoyaltyCurrencies' => $this->generateEncodeableTokenIds($currencies),
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

    public function test_it_works_with_empty_array_of_currencies(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, CreateCollectionMutation::getEncodableParams(
            mintPolicy: $mintPolicy = new MintPolicyParams(
                forceSingleMint: fake()->boolean(),
            ),
            explicitRoyaltyCurrencies: [],
        ));

        $response = $this->graphql($this->method, [
            'mintPolicy' => $mintPolicy->toArray(),
            'explicitRoyaltyCurrencies' => [],
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

    public function test_it_works_with_all_args(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, CreateCollectionMutation::getEncodableParams(
            mintPolicy: $mintPolicy = new MintPolicyParams(
                forceSingleMint: fake()->boolean(),
                maxTokenCount: fake()->numberBetween(),
                maxTokenSupply: fake()->numberBetween(),
            ),
            marketPolicy: $marketPolicy = new RoyaltyPolicyParams(
                beneficiary: app(Generator::class)->public_key(),
                percentage: fake()->numberBetween(1, 50)
            ),
            explicitRoyaltyCurrencies: $currencies = $this->generateCurrencies(fake()->numberBetween(1, 9)),
        ));

        $response = $this->graphql($this->method, [
            'mintPolicy' => $mintPolicy->toArray(),
            'marketPolicy' => [
                'royalty' => $marketPolicy->toArray(),
            ],
            'explicitRoyaltyCurrencies' => $this->generateEncodeableTokenIds($currencies),
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

    // Exception paths
    public function test_it_will_fail_with_empty_idempotency_key(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => false,
            ],
            'idempotencyKey' => '',
        ], true);

        $this->assertArraySubset(
            ['idempotencyKey' => ['The idempotency key field must have a value.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_short_idempotency_key(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => false,
            ],
            'idempotencyKey' => fake()->text(28),
        ], true);

        $this->assertArraySubset(
            ['idempotencyKey' => ['The idempotency key field must be at least 36 characters.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_long_idempotency_key(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => false,
            ],
            'idempotencyKey' => fake()->realTextBetween(256, 300),
        ], true);

        $this->assertArraySubset(
            ['idempotencyKey' => ['The idempotency key field must not be greater than 255 characters.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_args(): void
    {
        $response = $this->graphql($this->method, [], true);

        $this->assertStringContainsString(
            'Variable "$mintPolicy" of required type "MintPolicy!" was not provided.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_mint_policy(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => null,
        ], true);

        $this->assertStringContainsString(
            'Variable "$mintPolicy" of non-null type "MintPolicy!" must not be null.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_mint_policy(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => 'invalid',
        ], true);

        $this->assertStringContainsString(
            'Variable "$mintPolicy" got invalid value "invalid"; Expected type "MintPolicy" to be an object',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_force_single_mint(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [],
        ], true);

        $this->assertStringContainsString(
            'Variable "$mintPolicy" got invalid value []; Field "forceSingleMint" of required type "Boolean!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_force_single_mint(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => null,
            ],
        ], true);

        $this->assertStringContainsString(
            'value null at "mintPolicy.forceSingleMint"; Expected non-nullable type "Boolean!" not to be null',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_force_single_mint(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => 'invalid',
            ],
        ], true);

        $this->assertStringContainsString(
            'Boolean cannot represent a non boolean value',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_simulate(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'simulate' => 'invalid',
        ], true);

        $this->assertStringContainsString(
            'Boolean cannot represent a non boolean value',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_negative_token_count(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
                'maxTokenCount' => -1,
            ],
        ], true);

        $this->assertStringContainsString(
            'Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_zero_token_count(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
                'maxTokenCount' => 0,
            ],
        ], true);

        $this->assertArraySubset(
            ['mintPolicy.maxTokenCount' => ['The mint policy.max token count is too small, the minimum value it can be is 1.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_token_count(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
                'maxTokenCount' => 'invalid',
            ],
        ], true);

        $this->assertStringContainsString(
            'Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_overflow_token_count(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
                'maxTokenCount' => Hex::MAX_UINT128,
            ],
        ], true);

        $this->assertArraySubset(
            ['mintPolicy.maxTokenCount' => ['The mint policy.max token count is too large, the maximum value it can be is 18446744073709551615.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_negative_token_supply(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
                'maxTokenSupply' => -1,
            ],
        ], true);

        $this->assertStringContainsString(
            'Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_zero_token_supply(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
                'maxTokenSupply' => 0,
            ],
        ], true);

        $this->assertArraySubset(
            ['mintPolicy.maxTokenSupply' => ['The mint policy.max token supply is too small, the minimum value it can be is 1.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_token_supply(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
                'maxTokenSupply' => 'invalid',
            ],
        ], true);

        $this->assertStringContainsString(
            'Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_overflow_token_supply(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
                'maxTokenSupply' => Hex::MAX_UINT256,
            ],
        ], true);

        $this->assertArraySubset(
            ['mintPolicy.maxTokenSupply' => ['The mint policy.max token supply is too large, the maximum value it can be is 340282366920938463463374607431768211455.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_market_policy(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'marketPolicy' => 'invalid',
        ], true);

        $this->assertStringContainsString(
            'Variable "$marketPolicy" got invalid value "invalid"; Expected type "MarketPolicy" to be an object',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_empty_market_policy(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'marketPolicy' => [],
        ], true);

        $this->assertStringContainsString(
            'Variable "$marketPolicy" got invalid value []',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_royalty_policy(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'marketPolicy' => [
                'royalty' => 'invalid',
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$marketPolicy" got invalid value "invalid" at "marketPolicy.royalty"; Expected type "RoyaltyInput" to be an object',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'marketPolicy' => [
                'royalty' => null,
            ],
        ], true);

        $this->assertStringContainsString(
            'value null at "marketPolicy.royalty"; Expected non-nullable type "RoyaltyInput!" not to be null',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_beneficiary(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'marketPolicy' => [
                'royalty' => [
                    'beneficiary' => 'invalid',
                    'percentage' => fake()->numberBetween(1, 50),
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['marketPolicy.royalty.beneficiary' => ['The market policy.royalty.beneficiary is not a valid substrate account.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_beneficiary(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'marketPolicy' => [
                'royalty' => [
                    'percentage' => fake()->numberBetween(1, 50),
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "beneficiary" of required type "String!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_beneficiary(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'marketPolicy' => [
                'royalty' => [
                    'beneficiary' => null,
                    'percentage' => fake()->numberBetween(1, 50),
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$marketPolicy" got invalid value null at "marketPolicy.royalty.beneficiary"; Expected non-nullable type "String!" not to be null',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_percentage(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'marketPolicy' => [
                'royalty' => [
                    'beneficiary' => app(Generator::class)->public_key(),
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "percentage" of required type "Float!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_percentage(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'marketPolicy' => [
                'royalty' => [
                    'beneficiary' => app(Generator::class)->public_key(),
                    'percentage' => null,
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            '"marketPolicy.royalty.percentage"; Expected non-nullable type "Float!" not to be null',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_percentage(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'marketPolicy' => [
                'royalty' => [
                    'beneficiary' => app(Generator::class)->public_key(),
                    'percentage' => 'invalid',
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$marketPolicy" got invalid value "invalid" at "marketPolicy.royalty.percentage"; Float cannot represent non numeric value',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_negative_percentage(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'marketPolicy' => [
                'royalty' => [
                    'beneficiary' => app(Generator::class)->public_key(),
                    'percentage' => -1,
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['marketPolicy.royalty.percentage' => ['The market policy.royalty.percentage valid for a royalty is in the range of 0.1% to 50% and a maximum of 7 decimal places.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_zero_percentage(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'marketPolicy' => [
                'royalty' => [
                    'beneficiary' => app(Generator::class)->public_key(),
                    'percentage' => 0,
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['marketPolicy.royalty.percentage' => ['The market policy.royalty.percentage valid for a royalty is in the range of 0.1% to 50% and a maximum of 7 decimal places.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_more_than_max_percentage(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'marketPolicy' => [
                'royalty' => [
                    'beneficiary' => app(Generator::class)->public_key(),
                    'percentage' => 50.1,
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['marketPolicy.royalty.percentage' => ['The market policy.royalty.percentage valid for a royalty is in the range of 0.1% to 50% and a maximum of 7 decimal places.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_explicit_royalty_currencies(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'explicitRoyaltyCurrencies' => 'invalid',
        ], true);

        $this->assertStringContainsString(
            'Variable "$explicitRoyaltyCurrencies" got invalid value "invalid"; Expected type "MultiTokenIdInput" to be an object',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_currency_with_missing_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'explicitRoyaltyCurrencies' => $this->generateEncodeableTokenIds(array_merge($this->generateCurrencies(), [
                [
                    'tokenId' => fake()->numberBetween(),
                ],
            ])),
        ], true);

        $this->assertStringContainsString(
            'Field "collectionId" of required type "BigInt!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_currency_with_null_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'explicitRoyaltyCurrencies' => $this->generateEncodeableTokenIds(array_merge($this->generateCurrencies(), [
                [
                    'collectionId' => null,
                    'tokenId' => fake()->numberBetween(),
                ],
            ])),
        ], true);

        $this->assertStringContainsString(
            'Variable "$explicitRoyaltyCurrencies" got invalid value null at "explicitRoyaltyCurrencies[6].collectionId"; Expected non-nullable type "BigInt!" not to be null',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_currency_with_invalid_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'explicitRoyaltyCurrencies' => $this->generateEncodeableTokenIds(array_merge($this->generateCurrencies(), [
                [
                    'collectionId' => 'invalid',
                    'tokenId' => fake()->numberBetween(),
                ],
            ])),
        ], true);

        $this->assertStringContainsString(
            'Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_currency_with_missing_token_id(): void
    {
        $collection = Collection::factory()->create();

        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'explicitRoyaltyCurrencies' => array_merge($this->generateCurrencies(), [
                [
                    'collectionId' => $collection->collection_chain_id,
                ],
            ]),
        ], true);

        $this->assertStringContainsString(
            '"explicitRoyaltyCurrencies[0].tokenId"; Expected type "EncodableTokenIdInput" to be an object',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_currency_with_null_token_id(): void
    {
        $collection = Collection::factory()->create();

        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'explicitRoyaltyCurrencies' => array_merge($this->generateCurrencies(), [
                [
                    'collectionId' => $collection->collection_chain_id,
                    'tokenId' => null,
                ],
            ]),
        ], true);

        $this->assertStringContainsString(
            '"explicitRoyaltyCurrencies[0].tokenId"; Expected type "EncodableTokenIdInput" to be an object',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_currency_with_invalid_token_id(): void
    {
        $collection = Collection::factory()->create();

        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'explicitRoyaltyCurrencies' => array_merge($this->generateCurrencies(), [
                [
                    'collectionId' => $collection->collection_chain_id,
                    'tokenId' => 'invalid',
                ],
            ]),
        ], true);

        $this->assertStringContainsString(
            '"explicitRoyaltyCurrencies[0].tokenId"; Expected type "EncodableTokenIdInput" to be an object',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_duplicated_currency(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'explicitRoyaltyCurrencies' => $this->generateEncodeableTokenIds(array_merge($currencies = $this->generateCurrencies(), [$currencies[0]])),
        ], true);

        $this->assertArraySubset(
            ['explicitRoyaltyCurrencies' => ['The explicit royalty currencies must be an array of distinct multi assets.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_more_than_ten_currencies(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'explicitRoyaltyCurrencies' => $this->generateEncodeableTokenIds($this->generateCurrencies(11)),
        ], true);

        $this->assertArraySubset(
            ['explicitRoyaltyCurrencies' => ['The explicit royalty currencies field must not have more than 10 items.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_key_length(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'attributes' => [
                [
                    'key' => fake()->numerify(str_repeat('#', 257)),
                    'value' => fake()->realText(),
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['attributes.0.key' =>  ['The attributes.0.key field is too large.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_value_length(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'attributes' => [
                [
                    'key' => fake()->word(),
                    'value' => fake()->asciify(str_repeat('*', 1025)),
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['attributes.0.value' =>  ['The attributes.0.value field is too large.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_duplicate_attributes(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'attributes' => [
                ['key' => 'key', 'value' => 'value'],
                ['key' => 'key', 'value' => 'value'],
            ],
        ], true);
        $this->assertArraySubset(
            ['attributes' => ['The attributes must be an array of distinct attributes keys.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_passing_daemon_as_signing_account(): void
    {
        $response = $this->graphql($this->method, [
            'mintPolicy' => [
                'forceSingleMint' => fake()->boolean(),
            ],
            'signingAccount' => Account::daemonPublicKey(),
        ], true);

        $this->assertArraySubset(
            [
                'signingAccount' => ['The signing account is a daemon wallet and should not be used as a signingAccount.'],
            ],
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

    protected function generateEncodeableTokenIds($items)
    {
        return collect($items)->transform(function ($item) {
            $item['tokenId'] = $this->tokenIdEncoder->toEncodable($item['tokenId']);

            return $item;
        })->all();
    }
}
