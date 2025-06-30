<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\BurnMutation;
use Enjin\Platform\Models\Indexer\Account;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Indexer\Token;
use Enjin\Platform\Models\Indexer\TokenAccount;
use Enjin\Platform\Models\Substrate\BurnParams;
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

class BurnTest extends TestCaseGraphQL
{
    use MocksHttpClient;

    protected string $method = 'Burn';
    protected Codec $codec;

    protected Account $wallet;
    protected Collection $collection;
    protected Token $token;
    protected Encoder $tokenIdEncoder;
    protected TokenAccount $tokenAccount;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new Codec();
        $this->wallet = Address::daemon();
        $this->collection = Collection::factory(['owner_id' => $this->wallet->id])->create();
        $this->token = Token::factory([
            'collection_id' => $collectionId = $this->collection->id,
            'token_id' => $tokenId = fake()->numberBetween(),
            'id' => "{$collectionId}-{$tokenId}",
        ])->create();
        $this->tokenAccount = TokenAccount::factory([
            'account_id' => $this->wallet,
            'collection_id' => $this->collection,
            'token_id' => $this->token,
        ])->create();
        $this->tokenIdEncoder = new Integer($tokenId);
    }

    // Happy Path
    public function test_can_skip_validation(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, BurnMutation::getEncodableParams(
            collectionId: $collectionId = random_int(2000, 3000),
            burnParams: new BurnParams(
                tokenId: $this->tokenIdEncoder->encode(),
                amount: $amount = 1,
            ),
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'amount' => $amount,
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

    public function test_can_burn_a_token_with_default_values_using_adapter(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, BurnMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->id,
            burnParams: new BurnParams(
                tokenId: $this->tokenIdEncoder->encode(),
                amount: $amount = fake()->numberBetween(1, $this->tokenAccount->balance),
            ),
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'amount' => $amount,
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
    }

    public function test_it_can_simulate(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, BurnMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->id,
            burnParams: new BurnParams(
                tokenId: $this->tokenIdEncoder->encode(),
                amount: $amount = fake()->numberBetween(1, $this->tokenAccount->balance),
            ),
        ));

        $this->mockFee($feeDetails = app(Generator::class)->fee_details());
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'amount' => $amount,
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

    public function test_can_burn_a_token_with_default_values(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, BurnMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->id,
            burnParams: new BurnParams(
                tokenId: $this->tokenIdEncoder->encode(),
                amount: $amount = fake()->numberBetween(1, $this->tokenAccount->balance),
            ),
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'amount' => $amount,
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

    public function test_it_can_bypass_ownership(): void
    {
        $token = Token::factory([
            'collection_id' => $collection = Collection::factory()->create(['owner_id' => Account::factory()->create()]),
        ])->create();

        $response = $this->graphql($this->method, $params = [
            'collectionId' => $collection->id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_id),
                'amount' => fake()->numberBetween(1, 10),
                'removeTokenStorage' => true,
            ],
            'nonce' => fake()->numberBetween(),
        ], true);

        $this->assertEquals(
            [
                'collectionId' => ['The collection id provided is not owned by you.'],
                'params.amount' => ['The params.amount is invalid, the amount provided is bigger than the token account balance.'],
            ],
            $response['error']
        );

        IsCollectionOwner::bypass();
        $response = $this->graphql($this->method, $params, true);
        $this->assertEquals(
            ['params.amount' => ['The params.amount is invalid, the amount provided is bigger than the token account balance.']],
            $response['error']
        );
        IsCollectionOwner::unBypass();
    }

    public function test_can_burn_a_token_with_ss58_signing_account(): void
    {
        $wallet = Account::factory([
            'id' => $signingAccount = app(Generator::class)->public_key,
        ])->create();

        $collection = Collection::factory([
            'id' => $collectionId = fake()->numberBetween(2000),
            'owner_id' => $wallet,
        ])->create();

        $token = Token::factory([
            'collection_id' => $collection,
            'token_id' => $tokenId = fake()->numberBetween(),
        ])->create();

        TokenAccount::factory([
            'collection_id' => $collection,
            'token_id' => $token,
            'account_id' => $wallet,
            'balance' => $amount = fake()->numberBetween(1, $this->tokenAccount->balance),
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, BurnMutation::getEncodableParams(
            collectionId: $collectionId,
            burnParams: new BurnParams(
                tokenId: $tokenId,
                amount: $amount,
            ),
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
                'amount' => $amount,
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

    public function test_can_burn_a_token_with_public_key_signing_account(): void
    {
        $wallet = Account::factory([
            'id' => $signingAccount = app(Generator::class)->public_key,
        ])->create();

        $collection = Collection::factory([
            'id' => $collectionId = fake()->numberBetween(2000),
            'owner_id' => $wallet,
        ])->create();

        $token = Token::factory([
            'collection_id' => $collection,
            'token_id' => $tokenId = fake()->numberBetween(),
        ])->create();

        TokenAccount::factory([
            'collection_id' => $collection,
            'token_id' => $token,
            'account_id' => $wallet,
            'balance' => $amount = fake()->numberBetween(1, $this->tokenAccount->balance),
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, BurnMutation::getEncodableParams(
            collectionId: $collectionId,
            burnParams: new BurnParams(
                tokenId: $tokenId,
                amount: $amount,
            ),
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
                'amount' => $amount,
            ],
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

    public function test_can_burn_a_token_with_keepalive(): void
    {
        $encodedData = TransactionSerializer::encode(
            $this->method,
            BurnMutation::getEncodableParams(
                collectionId: $collectionId = $this->collection->id,
                burnParams: new BurnParams(
                    tokenId: $this->tokenIdEncoder->encode(),
                    amount: $amount = fake()->numberBetween(1, $this->tokenAccount->balance),
                )
            ),
        );

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'amount' => $amount,
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

    public function test_can_burn_a_token_with_remove_token_storage(): void
    {
        $encodedData = TransactionSerializer::encode(
            $this->method,
            BurnMutation::getEncodableParams(
                collectionId: $collectionId = $this->collection->id,
                burnParams: new BurnParams(
                    tokenId: $this->tokenIdEncoder->encode(),
                    amount: $amount = fake()->numberBetween(1, $this->tokenAccount->balance),
                    removeTokenStorage: $removeTokenStorage = fake()->boolean(),
                )
            ),
        );

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'amount' => $amount,
                'removeTokenStorage' => $removeTokenStorage,
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


        $encodedData = TransactionSerializer::encode(
            $this->method,
            BurnMutation::getEncodableParams(
                collectionId: $collectionId = $this->collection->id,
                burnParams: new BurnParams(
                    tokenId: $this->tokenIdEncoder->encode(),
                    amount: 0,
                    removeTokenStorage: true,
                )
            ),
        );

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'amount' => 0,
                'removeTokenStorage' => true,
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

    public function test_it_can_burn_a_token_without_being_collection_owner(): void
    {
        $wallet = Account::factory([
            'id' => $signingAccount = app(Generator::class)->public_key,
        ])->create();

        $collection = Collection::factory([
            'id' => $collectionId = fake()->numberBetween(2000),
            'owner_id' => $this->wallet,
        ])->create();

        $token = Token::factory([
            'collection_id' => $collection,
            'token_id' => $tokenId = fake()->numberBetween(),
        ])->create();

        TokenAccount::factory([
            'collection_id' => $collection,
            'token_id' => $token,
            'account_id' => $wallet,
            'balance' => $amount = fake()->numberBetween(1, $this->tokenAccount->balance),
        ])->create();

        $encodedData = TransactionSerializer::encode(
            $this->method,
            BurnMutation::getEncodableParams(
                collectionId: $collectionId,
                burnParams: new BurnParams(
                    tokenId: $tokenId,
                    amount: $amount,
                    removeTokenStorage: false,
                )
            ),
        );

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => [
                    'integer' => $tokenId,
                ],
                'amount' => $amount,
                'removeTokenStorage' => false,
            ],
            'signingAccount' => $signingAccount,
        ]);

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
        ], $response);

        $this->assertDatabaseHas('transactions', [
            'id' => $response['id'],
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encoded_data' => $encodedData,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_can_burn_a_token_with_all_args(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, BurnMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->id,
            burnParams: $params = new BurnParams(
                tokenId: $this->tokenIdEncoder->encode(),
                amount: fake()->numberBetween(1, $this->tokenAccount->balance),
                removeTokenStorage: fake()->boolean(),
            ),
        ));

        $params = $params->toArray();
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

        $response = $this->graphql($this->method, [
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

    public function test_can_burn_a_token_with_bigint_tokenid(): void
    {
        $collection = Collection::factory([
            'id' => fake()->numberBetween(2000),
            'owner_id' => $this->wallet,
        ])->create();

        $token = Token::factory([
            'collection_id' => $collection,
            'token_id' => Hex::MAX_UINT128,
        ])->create();

        $tokenAccount = TokenAccount::factory([
            'collection_id' => $collection,
            'token_id' => $token,
            'account_id' => $this->wallet,
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, BurnMutation::getEncodableParams(
            collectionId: $collectionId = $collection->id,
            burnParams: $params = new BurnParams(
                tokenId: $this->tokenIdEncoder->encode($token->token_id),
                amount: fake()->numberBetween(1, $tokenAccount->balance),
            ),
        ));

        $params = $params->toArray();
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($token->token_id);

        $response = $this->graphql($this->method, [
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

    public function test_can_burn_a_token_with_bigint_amount(): void
    {
        $collection = Collection::factory([
            'id' => fake()->numberBetween(2000),
            'owner_id' => $this->wallet,
        ])->create();

        $token = Token::factory([
            'collection_id' => $collection,
            'token_id' => Hex::MAX_UINT128,
        ])->create();

        TokenAccount::factory([
            'collection_id' => $collection,
            'token_id' => $token,
            'account_id' => $this->wallet,
            'balance' => $balance = Hex::MAX_UINT128,
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, BurnMutation::getEncodableParams(
            collectionId: $collectionId = $collection->id,
            burnParams: $params = new BurnParams(
                tokenId: $token->token_id,
                amount: $balance,
            ),
        ));

        $params = $params->toArray();
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($token->token_id);

        $response = $this->graphql($this->method, [
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

    // Exception Path

    public function test_it_will_fail_collection_id_that_doesnt_exists(): void
    {
        Collection::where('id', '=', $collectionId = fake()->numberBetween(2000))?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['collectionId' => ['The selected collection id is invalid.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_token_id_that_doesnt_exists(): void
    {
        Token::where('token_id', '=', $tokenId = fake()->numberBetween())?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['params.tokenId' => ['The params.token id does not exist in the specified collection.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_invalid_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => 'not_valid',
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value "not_valid"; Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_invalid_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'params' => [
                'tokenId' => 'not_valid',
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$params" got invalid value "not_valid" at "params.tokenId"; Expected type "EncodableTokenIdInput" to be an object',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_negative_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => -1,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value -1; Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_negative_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'params' => [
                'tokenId' => -1,
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$params" got invalid value -1 at "params.tokenId"; Expected type "EncodableTokenIdInput" to be an object',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_negative_amount(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'amount' => -1,
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$params" got invalid value -1 at "params.amount"; Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_zero_amount(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'amount' => 0,
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['params.amount' => ['The params.amount is too small, the minimum value it can be is 1.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_no_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of required type "BigInt!" was not provided.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_no_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'params' => [
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "tokenId" of required type "EncodableTokenIdInput!" was not provided',
            $response['errors'][0]['message']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_no_amount(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "amount" of required type "BigInt!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_invalid_keepalive(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
                'keepAlive' => 'invalid',
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$params" got invalid value "invalid" at "params.keepAlive"; Boolean cannot represent a non boolean value',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_invalid_removetokenstorage(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
                'removeTokenStorage' => 'invalid',
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$params" got invalid value "invalid" at "params.removeTokenStorage"; Boolean cannot represent a non boolean value',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_empty_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'params' => [],
        ], true);

        $this->assertStringContainsString(
            'Variable "$params" got invalid value []; Field "tokenId" of required type "EncodableTokenIdInput!" was not provide',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_when_trying_to_burn_more_than_balance(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'amount' => fake()->numberBetween($this->tokenAccount->balance),
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['params.amount' => ['The params.amount is invalid, the amount provided is bigger than the token account balance.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fails_burn_a_token_with_remove_storage_without_being_collection_owner(): void
    {
        $wallet = Account::factory([
            'id' => $signingAccount = app(Generator::class)->public_key,
        ])->create();

        $collection = Collection::factory([
            'id' => $collectionId = fake()->numberBetween(2000),
            'owner_id' => $this->wallet,
        ])->create();

        $token = Token::factory([
            'collection_id' => $collection,
            'token_id' => $tokenId = fake()->numberBetween(),
        ])->create();

        TokenAccount::factory([
            'collection_id' => $collection,
            'token_id' => $token,
            'account_id' => $wallet,
            'balance' => $amount = fake()->numberBetween(1, $this->tokenAccount->balance),
        ])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => [
                    'integer' => $tokenId,
                ],
                'amount' => $amount,
                'removeTokenStorage' => true,
            ],
            'signingAccount' => $signingAccount,
        ], true);

        $this->assertArrayContainsArray([
            'collectionId' => ['The collection id provided is not owned by you.'],
        ], $response['error']);

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
