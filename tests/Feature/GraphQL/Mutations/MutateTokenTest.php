<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\MutateTokenMutation;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Substrate\RoyaltyPolicyParams;
use Enjin\Platform\Models\Substrate\TokenMarketBehaviorParams;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Token\Encoder;
use Enjin\Platform\Services\Token\Encoders\Integer;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Support\MocksWebsocketClient;
use Facades\Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

class MutateTokenTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;
    use MocksWebsocketClient;

    protected string $method = 'MutateToken';
    protected Codec $codec;
    protected string $defaultAccount;
    protected Model $collection;
    protected Model $token;
    protected Encoder $tokenIdEncoder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->codec = new Codec();
        $this->defaultAccount = Account::daemonPublicKey();

        $this->collection = Collection::factory()->create();
        $this->token = Token::factory([
            'collection_id' => $this->collection,
        ])->create();
        $this->tokenIdEncoder = new Integer($this->token->token_chain_id);
    }

    // Happy Path
    public function test_it_can_skip_validation(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, MutateTokenMutation::getEncodableParams(
            collectionId: $collectionId = random_int(1, 1000),
            tokenId: $this->tokenIdEncoder->encode(),
            listingForbidden: $listingForbidden = fake()->boolean(),
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'listingForbidden' => $listingForbidden,
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

    public function test_it_can_mutate_a_token_with_listing_forbidden_using_adapter(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, MutateTokenMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode(),
            listingForbidden: $listingForbidden = fake()->boolean(),
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'listingForbidden' => $listingForbidden,
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
    }

    public function test_it_can_simulate(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, MutateTokenMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode(),
            listingForbidden: $listingForbidden = fake()->boolean(),
        ));

        $this->mockFee($feeDetails = app(Generator::class)->fee_details());
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'listingForbidden' => $listingForbidden,
            ],
            'simulate' => true,
        ]);

        $this->assertArraySubset([
            'id' => null,
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'fee' => $feeDetails['fakeSum'],
            'deposit' => null,
            'wallet' => [
                'account' => [
                    'publicKey' => $this->defaultAccount,
                ],
            ],
        ], $response);

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_can_mutate_a_token_with_listing_forbidden(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, MutateTokenMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode(),
            listingForbidden: $listingForbidden = fake()->boolean(),
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'listingForbidden' => $listingForbidden,
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

    public function test_it_can_mutate_a_token_with_ss58_signing_account(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, MutateTokenMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode(),
            listingForbidden: $listingForbidden = fake()->boolean(),
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'listingForbidden' => $listingForbidden,
            ],
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

    public function test_it_can_mutate_a_token_with_public_key_signing_account(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, MutateTokenMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode(),
            listingForbidden: $listingForbidden = fake()->boolean(),
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'listingForbidden' => $listingForbidden,
            ],
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

    public function test_it_can_mutate_a_token_with_empty_behavior(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, MutateTokenMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode(),
            behavior: [],
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'behavior' => [],
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

    public function test_it_can_mutate_a_token_with_behavior_is_currency(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, MutateTokenMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode(),
            behavior: new TokenMarketBehaviorParams(isCurrency: true),
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'behavior' => [
                    'isCurrency' => true,
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

    public function test_it_can_mutate_a_token_with_behavior_has_royalty(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, MutateTokenMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode(),
            behavior: new TokenMarketBehaviorParams(hasRoyalty: new RoyaltyPolicyParams(
                beneficiary: $beneficiary = app(Generator::class)->public_key(),
                percentage: $percentage = fake()->numberBetween(1, 40),
            )),
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'behavior' => [
                    'hasRoyalty' => [
                        'beneficiary' => $beneficiary,
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

    public function test_it_can_mutate_a_token_with_all_fields(): void
    {
        $behavior = fake()->randomElement([
            [],
            new TokenMarketBehaviorParams(isCurrency: true),
            new TokenMarketBehaviorParams(hasRoyalty: new RoyaltyPolicyParams(
                beneficiary: app(Generator::class)->chain_address(),
                percentage: fake()->numberBetween(1, 40),
            )),
        ]);

        $encodedData = TransactionSerializer::encode($this->method, MutateTokenMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode(),
            behavior: $behavior,
            listingForbidden: $listingForbidden = fake()->boolean(),
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'behavior' => is_array($behavior) ? [] : $behavior->toArray(),
                'listingForbidden' => $listingForbidden,
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

    public function test_it_will_fail_with_no_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'listingForbidden' => fake()->boolean(),
            ],
        ], true);

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
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'listingForbidden' => fake()->boolean(),
            ],
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
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'listingForbidden' => fake()->boolean(),
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
        Collection::where('collection_chain_id', '=', $collectionId = fake()->numberBetween())?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'listingForbidden' => fake()->boolean(),
            ],
        ], true);

        $this->assertArraySubset(
            ['collectionId' => ['The selected collection id is invalid.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'mutation' => [
                'listingForbidden' => fake()->boolean(),
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$tokenId" of required type "EncodableTokenIdInput!" was not provided.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => null,
            'mutation' => [
                'listingForbidden' => fake()->boolean(),
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$tokenId" of non-null type "EncodableTokenIdInput!" must not be null.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => ['integer' => 'invalid'],
            'mutation' => [
                'listingForbidden' => fake()->boolean(),
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$tokenId" got invalid value "invalid" at "tokenId.integer"; Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_token_id_that_doesnt_exists(): void
    {
        Token::where('token_chain_id', '=', $tokenId = fake()->numberBetween())?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => ['integer' => $tokenId],
            'mutation' => [
                'listingForbidden' => fake()->boolean(),
            ],
        ], true);

        $this->assertArraySubset(
            ['tokenId' => ['The token id does not exist in the specified collection.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_mutation(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
        ], true);

        $this->assertStringContainsString(
            'Variable "$mutation" of required type "TokenMutationInput!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_mutation(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => null,
        ], true);

        $this->assertStringContainsString(
            'Variable "$mutation" of non-null type "TokenMutationInput!" must not be null',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_mutation(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => 'invalid',
        ], true);

        $this->assertStringContainsString(
            'Variable "$mutation" got invalid value',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_empty_mutation(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [],
        ], true);

        $this->assertArraySubset(
            ['mutation.behavior' => ['The mutation.behavior field is required when mutation.listing forbidden is not present.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_only_listing_forbidden_equals_null(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'listingForbidden' => null,
            ],
        ], true);

        $this->assertArraySubset(
            ['mutation.behavior' => ['The mutation.behavior field is required when mutation.listing forbidden is not present.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_listing_forbidden(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'listingForbidden' => 'invalid',
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$mutation" got invalid value "invalid" at "mutation.listingForbidden"; Boolean cannot represent a non boolean value',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_only_behavior_equals_null(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'behavior' => null,
            ],
        ], true);

        $this->assertArraySubset(
            ['mutation.behavior' => ['The mutation.behavior field is required when mutation.listing forbidden is not present.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_behavior(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'behavior' => 'invalid',
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$mutation" got invalid value "invalid" at "mutation.behavior"; Expected type "TokenMarketBehaviorInput" to be an object',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_beneficiary_in_has_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'behavior' => [
                    'hasRoyalty' => [
                        'percentage' => 20,
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

    public function test_it_will_fail_with_invalid_beneficiary_in_has_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'behavior' => [
                    'hasRoyalty' => [
                        'percentage' => 20,
                        'beneficiary' => 'invalid',
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['mutation.behavior.hasRoyalty.beneficiary' => ['The mutation.behavior.has royalty.beneficiary is not a valid substrate account.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_beneficiary_in_has_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'behavior' => [
                    'hasRoyalty' => [
                        'percentage' => 20,
                        'beneficiary' => null,
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$mutation" got invalid value null at "mutation.behavior.hasRoyalty.beneficiary"; Expected non-nullable type "String!" not to be null',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_percentage_in_has_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'behavior' => [
                    'hasRoyalty' => [
                        'beneficiary' => 'invalid',
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "percentage" of required type "Float!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_percentage_in_has_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'behavior' => [
                    'hasRoyalty' => [
                        'beneficiary' => $this->defaultAccount,
                        'percentage' => null,
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$mutation" got invalid value null at "mutation.behavior.hasRoyalty.percentage"; Expected non-nullable type "Float!" not to be null',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_negative_percentage_in_has_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'behavior' => [
                    'hasRoyalty' => [
                        'beneficiary' => $this->defaultAccount,
                        'percentage' => -1,
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['mutation.behavior.hasRoyalty.percentage' => ['The mutation.behavior.has royalty.percentage valid for a royalty is in the range of 0.1% to 50% and a maximum of 7 decimal places.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_zero_percentage_in_has_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'behavior' => [
                    'hasRoyalty' => [
                        'beneficiary' => $this->defaultAccount,
                        'percentage' => 0,
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['mutation.behavior.hasRoyalty.percentage' => ['The mutation.behavior.has royalty.percentage valid for a royalty is in the range of 0.1% to 50% and a maximum of 7 decimal places.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_percentage_greater_than_max_in_has_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'behavior' => [
                    'hasRoyalty' => [
                        'beneficiary' => $this->defaultAccount,
                        'percentage' => 51,
                    ],
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['mutation.behavior.hasRoyalty.percentage' => ['The mutation.behavior.has royalty.percentage valid for a royalty is in the range of 0.1% to 50% and a maximum of 7 decimal places.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_percentage_in_has_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' =>$this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'behavior' => [
                    'hasRoyalty' => [
                        'beneficiary' => $this->defaultAccount,
                        'percentage' => 'invalid',
                    ],
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$mutation" got invalid value "invalid" at "mutation.behavior.hasRoyalty.percentage"; Float cannot represent non numeric value',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_only_is_currency_null_in_has_royalty(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'behavior' => [
                    'isCurrency' => null,
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['mutation.behavior.isCurrency' => ['The isCurrency parameter only accepts true. If you don\'t want it to be a currency, don\'t pass it.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_is_currency_equals_to_false(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'behavior' => [
                    'isCurrency' => false,
                ],
            ],
        ], true);

        $this->assertArraySubset(
            ['mutation.behavior.isCurrency' => ['The isCurrency parameter only accepts true. If you don\'t want it to be a currency, don\'t pass it.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_is_currency(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'mutation' => [
                'behavior' => [
                    'isCurrency' => 'invalid',
                ],
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$mutation" got invalid value "invalid" at "mutation.behavior.isCurrency"; Boolean cannot represent a non boolean value',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
