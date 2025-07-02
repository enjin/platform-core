<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\SimpleTransferTokenMutation;
use Enjin\Platform\Models\Indexer\Account;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Indexer\CollectionAccount;
use Enjin\Platform\Models\Indexer\Token;
use Enjin\Platform\Models\Indexer\TokenAccount;
use Enjin\Platform\Models\Substrate\SimpleTransferParams;
use Enjin\Platform\Rules\IsCollectionOwner;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Token\Encoder;
use Enjin\Platform\Services\Token\Encoders\Integer;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Support\MocksHttpClient;
use Facades\Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Faker\Generator;
use Illuminate\Support\Facades\Event;
use Override;

class SimpleTransferTokenTest extends TestCaseGraphQL
{
    use MocksHttpClient;

    protected string $method = 'SimpleTransferToken';
    protected Codec $codec;

    protected string $defaultAccount;
    protected Account $wallet;
    protected Collection $collection;
    protected CollectionAccount $collectionAccount;
    protected Token $token;
    protected Encoder $tokenIdInput;
    protected TokenAccount $tokenAccount;
    protected Account $recipient;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->codec = new Codec();
        $this->wallet = $this->getDaemonAccount();

        $this->recipient = Account::factory()->create();

        $this->collection = Collection::factory([
            'owner_id' => $ownerId = $this->wallet,
        ])->create();

        $this->token = Token::factory([
            'collection_id' => $collectionId = $this->collection->id,
            'token_id' => $tokenId = fake()->numberBetween(),
            'id' => "{$collectionId}-{$tokenId}",
        ])->create();

        $this->collectionAccount = CollectionAccount::factory([
            'collection_id' => $collectionId,
            'account_id' => $ownerId,
            'account_count' => 1,
        ])->create();

        $this->tokenAccount = TokenAccount::factory([
            'account_id' => $ownerId,
            'collection_id' => $collectionId,
            'token_id' => $this->token->id,
            'id' => "{$ownerId}-{$collectionId}-{$tokenId}",
        ])->create();

        $this->tokenIdInput = new Integer($tokenId);
    }

    public function test_it_can_skip_validation(): void
    {
        $signingWallet = Account::factory([
            //            'managed' => false,
        ])->create();

        $tokenAccount = TokenAccount::factory([
            'account_id' => $signingWallet,
        ])->create();

        $token = Token::find($tokenAccount->token_id);
        $collection = Collection::find($tokenAccount->collection_id);

        CollectionAccount::factory([
            'collection_id' => $collection,
            'account_id' => $signingWallet,
            'account_count' => 1,
        ])->create();

        $encodedData = TransactionSerializer::encode('Transfer', SimpleTransferTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->id,
            collectionId: $collectionId = $collection->id,
            simpleTransferParams: $params = new SimpleTransferParams(
                tokenId: $this->tokenIdInput->encode($token->token_id),
                amount: fake()->numberBetween(1, $tokenAccount->balance),
            ),
        ));

        $params = $params->toArray()['Simple'];
        $params['tokenId'] = $this->tokenIdInput->toEncodable($token->token_id);

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($recipient),
            'params' => $params,
            'signingAccount' => SS58Address::encode($signingWallet->public_key),
            'skipValidation' => true,
        ]);

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => [
                'account' => [
                    'publicKey' => $signingWallet->id,
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

    public function test_it_can_transfer_token_using_adapter(): void
    {
        $encodedData = TransactionSerializer::encode('Transfer', SimpleTransferTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->id,
            collectionId: $collectionId = $this->collection->id,
            simpleTransferParams: $params = new SimpleTransferParams(
                tokenId: $this->tokenIdInput->encode(),
                amount: fake()->numberBetween(1, $this->tokenAccount->balance),
            ),
        ));

        $params = $params->toArray()['Simple'];
        $params['tokenId'] = $this->tokenIdInput->toEncodable();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($recipient),
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
    }

    public function test_it_can_simulate(): void
    {
        $encodedData = TransactionSerializer::encode('Transfer', SimpleTransferTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->id,
            collectionId: $collectionId = $this->collection->id,
            simpleTransferParams: $params = new SimpleTransferParams(
                tokenId: $this->tokenIdInput->encode(),
                amount: fake()->numberBetween(1, $this->tokenAccount->balance),
            ),
        ));

        $params = $params->toArray()['Simple'];
        $params['tokenId'] = $this->tokenIdInput->toEncodable();

        $this->mockFee($feeDetails = app(Generator::class)->fee_details());
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($recipient),
            'params' => $params,
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
        $signingWallet = Account::factory()->create();
        $collection = Collection::factory()->create(['owner_id' => $signingWallet]);
        $token = Token::factory([
            'collection_id' => $collection,
        ])->create();

        $response = $this->graphql($this->method, $params = [
            'collectionId' => $collection->id,
            'recipient' => SS58Address::encode($this->recipient->id),
            'params' => [
                'tokenId' => $this->tokenIdInput->toEncodable($token->token_id),
                'amount' => fake()->numberBetween(),
                'keepAlive' => fake()->boolean(),
            ],
            'nonce' => fake()->numberBetween(),
        ], true);

        $this->assertArrayContainsArray(
            ['params.amount' => ['The params.amount is invalid, the amount provided is bigger than the token account balance.']],
            $response['error']
        );

        IsCollectionOwner::bypass();
        $response = $this->graphql($this->method, $params, true);
        $this->assertArrayContainsArray(
            [
                'params.amount' => ['The params.amount is invalid, the amount provided is bigger than the token account balance.'],
            ],
            $response['error']
        );
        IsCollectionOwner::unBypass();
    }

    public function test_it_can_transfer_token(): void
    {
        $encodedData = TransactionSerializer::encode('Transfer', SimpleTransferTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->id,
            collectionId: $collectionId = $this->collection->id,
            simpleTransferParams: $params = new SimpleTransferParams(
                tokenId: $this->tokenIdInput->encode(),
                amount: fake()->numberBetween(1, $this->tokenAccount->balance),
            ),
        ));

        $params = $params->toArray()['Simple'];
        $params['tokenId'] = $this->tokenIdInput->toEncodable();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($recipient),
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

    public function test_it_can_transfer_token_without_pass_keep_alive(): void
    {
        $encodedData = TransactionSerializer::encode('Transfer', SimpleTransferTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->id,
            collectionId: $collectionId = $this->collection->id,
            simpleTransferParams: $params = new SimpleTransferParams(
                tokenId: $this->tokenIdInput->encode(),
                amount: fake()->numberBetween(1, $this->tokenAccount->balance),
            ),
        ));

        $params = $params->toArray()['Simple'];
        $params['tokenId'] = $this->tokenIdInput->toEncodable();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($recipient),
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

    public function test_it_can_transfer_token_with_signing_wallet(): void
    {
        $signingWallet = Account::factory([
            //            'managed' => true,
        ])->create();

        $collection = Collection::factory(['owner_id' => $signingWallet->id])->create();
        $token = Token::factory([
            'collection_id' => $collectionId = $collection->id,
            'token_id' => $tokenId = fake()->numberBetween(),
            'id' => "{$collectionId}-{$tokenId}",
        ])->create();

        $tokenAccount = TokenAccount::factory([
            'account_id' => $signingWallet,
            'collection_id' => $collectionId,
            'token_id' => $token,
        ])->create();

        CollectionAccount::factory([
            'collection_id' => $collectionId,
            'account_id' => $signingWallet,
            'account_count' => 1,
        ])->create();

        $encodedData = TransactionSerializer::encode('Transfer', SimpleTransferTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->id,
            collectionId: $collectionId = $collection->id,
            simpleTransferParams: $params = new SimpleTransferParams(
                tokenId: $this->tokenIdInput->encode($token->token_id),
                amount: fake()->numberBetween(1, $tokenAccount->balance),
            ),
        ));

        $params = $params->toArray()['Simple'];
        $params['tokenId'] = $this->tokenIdInput->toEncodable($token->token_id);

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($recipient),
            'params' => $params,
            'signingAccount' => SS58Address::encode($signingWallet->public_key),
        ]);

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => [
                'account' => [
                    'publicKey' => $signingWallet->id,
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

    public function test_it_can_transfer_token_with_public_keysigning_wallet(): void
    {
        $wallet = Account::factory([
            'id' => $signingAccount = app(Generator::class)->public_key,
        ])->create();
        $collection = Collection::factory(['owner_id' => $wallet])->create();
        $token = Token::factory(['collection_id' => $collection])->create();
        $tokenAccount = TokenAccount::factory([
            'account_id' => $wallet,
            'collection_id' => $collection,
            'token_id' => $token,
        ])->create();
        CollectionAccount::factory([
            'collection_id' => $collection,
            'account_id' => $wallet,
            'account_count' => 1,
        ])->create();

        $encodedData = TransactionSerializer::encode('Transfer', SimpleTransferTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->id,
            collectionId: $collectionId = $collection->id,
            simpleTransferParams: $params = new SimpleTransferParams(
                tokenId: $this->tokenIdInput->encode($token->token_id),
                amount: fake()->numberBetween(1, $tokenAccount->balance),
            ),
        ));

        $params = $params->toArray()['Simple'];
        $params['tokenId'] = $this->tokenIdInput->toEncodable($token->token_id);

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($recipient),
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

    public function test_it_can_transfer_token_with_null_signing_wallet(): void
    {
        $encodedData = TransactionSerializer::encode('Transfer', SimpleTransferTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->id,
            collectionId: $collectionId = $this->collection->id,
            simpleTransferParams: $params = new SimpleTransferParams(
                tokenId: $this->tokenIdInput->encode(),
                amount: fake()->numberBetween(1, $this->tokenAccount->balance),
            ),
        ));

        $params = $params->toArray()['Simple'];
        $params['tokenId'] = $this->tokenIdInput->toEncodable();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($recipient),
            'params' => $params,
            'signingAccount' => null,
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

    public function test_it_can_transfer_token_with_empty_signing_account_and_works_as_daemon(): void
    {
        $encodedData = TransactionSerializer::encode('Transfer', SimpleTransferTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->id,
            collectionId: $collectionId = $this->collection->id,
            simpleTransferParams: $params = new SimpleTransferParams(
                tokenId: $this->tokenIdInput->encode(),
                amount: fake()->numberBetween(1, $this->tokenAccount->balance),
            ),
        ));

        $params = $params->toArray()['Simple'];
        $params['tokenId'] = $this->tokenIdInput->toEncodable();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($recipient),
            'params' => $params,
            'signingAccount' => '',
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

    public function test_it_can_transfer_token_with_recipient_that_doesnt_exists(): void
    {
        Account::find($publicKey = app(Generator::class)->public_key())?->delete();

        $encodedData = TransactionSerializer::encode('Transfer', SimpleTransferTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $publicKey,
            collectionId: $collectionId = $this->collection->id,
            simpleTransferParams: $params = new SimpleTransferParams(
                tokenId: $this->tokenIdInput->encode(),
                amount: fake()->numberBetween(1, $this->tokenAccount->balance),
            ),
        ));

        $params = $params->toArray()['Simple'];
        $params['tokenId'] = $this->tokenIdInput->toEncodable();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($recipient),
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
            'id' => $publicKey,
        ]);
    }

    public function test_it_can_transfer_token_with_bigint_collection_id(): void
    {
        Collection::where('id', Hex::MAX_UINT128)->update(['id' => fake()->numberBetween()]);
        $collection = Collection::factory([
            'id' => Hex::MAX_UINT128,
            'owner_id' => $this->wallet,
        ])->create();

        CollectionAccount::factory([
            'collection_id' => $collection,
            'account_id' => $this->wallet,
            'account_count' => 1,
        ])->create();

        $token = Token::factory([
            'collection_id' => $collection,
        ])->create();

        $tokenAccount = TokenAccount::factory([
            'collection_id' => $collection,
            'token_id' => $token,
            'account_id' => $this->wallet,
        ])->create();

        $encodedData = TransactionSerializer::encode('Transfer', SimpleTransferTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->id,
            collectionId: $collectionId = $collection->id,
            simpleTransferParams: $params = new SimpleTransferParams(
                tokenId: $this->tokenIdInput->encode($token->token_id),
                amount: fake()->numberBetween(1, $tokenAccount->balance),
            ),
        ));

        $params = $params->toArray()['Simple'];
        $params['tokenId'] = $this->tokenIdInput->toEncodable($token->token_id);

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($recipient),
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

    public function test_it_can_transfer_token_with_bigint_token_id(): void
    {
        $collection = Collection::factory()->create(['owner_id' => $this->wallet]);
        CollectionAccount::factory([
            'collection_id' => $collection,
            'account_id' => $this->wallet,
            'account_count' => 1,
        ])->create();
        Token::where('token_id', Hex::MAX_UINT128)->update(['token_id' => random_int(1, 1000)]);
        $token = Token::factory([
            'collection_id' => $collection,
            'token_id' => Hex::MAX_UINT128,
        ])->create();
        $tokenAccount = TokenAccount::factory([
            'collection_id' => $collection,
            'token_id' => $token,
            'account_id' => $this->wallet,
        ])->create();

        $encodedData = TransactionSerializer::encode('Transfer', SimpleTransferTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->id,
            collectionId: $collectionId = $collection->id,
            simpleTransferParams: $params = new SimpleTransferParams(
                tokenId: $this->tokenIdInput->encode($token->token_id),
                amount: fake()->numberBetween(1, $tokenAccount->balance),
            ),
        ));

        $params = $params->toArray()['Simple'];
        $params['tokenId'] = $this->tokenIdInput->toEncodable($token->token_id);

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($recipient),
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

    public function test_it_will_fail_collection_id_doesnt_exists(): void
    {
        Collection::where('id', '=', $collectionId = fake()->numberBetween(2000))?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($this->recipient->id),
            'params' => [
                'tokenId' => $this->tokenIdInput->toEncodable(),
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['collectionId' => ['The selected collection id is invalid.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_invalid_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => 'not_valid',
            'recipient' => $this->recipient->id,
            'params' => [
                'tokenId' => $this->tokenIdInput->toEncodable(),
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value "not_valid"; Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_null_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => null,
            'recipient' => $this->recipient->id,
            'params' => [
                'tokenId' => $this->tokenIdInput->toEncodable(),
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of non-null type "BigInt!" must not be null.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_no_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => $this->recipient->id,
            'params' => [
                'tokenId' => $this->tokenIdInput->toEncodable(),
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of required type "BigInt!" was not provided.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_invalid_recipient(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'recipient' => 'not_valid',
            'params' => [
                'tokenId' => $this->tokenIdInput->toEncodable(),
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['recipient' => ['The recipient is not a valid substrate account.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_null_recipient(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'recipient' => null,
            'params' => [
                'tokenId' => $this->tokenIdInput->toEncodable(),
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipient" of non-null type "String!" must not be null.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_no_recipient(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'params' => [
                'tokenId' => $this->tokenIdInput->toEncodable(),
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipient" of required type "String!" was not provided.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_token_doesnt_exists(): void
    {
        Token::where('token_id', '=', $tokenId = fake()->numberBetween())?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'recipient' => SS58Address::encode($this->recipient->id),
            'params' => [
                'tokenId' => $this->tokenIdInput->toEncodable($tokenId),
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['params.tokenId' => ['The params.token id does not exist in the specified collection.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_invalid_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'recipient' => $this->recipient->id,
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

    public function test_it_will_fail_null_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'recipient' => $this->recipient->id,
            'params' => [
                'tokenId' => null,
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$params" got invalid value null at "params.tokenId"; Expected non-nullable type "EncodableTokenIdInput!',
            $response['errors'][0]['message']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_no_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'recipient' => $this->recipient->id,
            'params' => [
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "tokenId" of required type "EncodableTokenIdInput!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_invalid_amount(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'recipient' => $this->recipient->id,
            'params' => [
                'tokenId' => $this->tokenIdInput->toEncodable(),
                'amount' => 'not_valid',
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$params" got invalid value "not_valid" at "params.amount"; Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_null_amount(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'recipient' => $this->recipient->id,
            'params' => [
                'tokenId' => $this->tokenIdInput->toEncodable(),
                'amount' => null,
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$params" got invalid value null at "params.amount"; Expected non-nullable type "BigInt!" not to be null',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_no_amount(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'recipient' => $this->recipient->id,
            'params' => [
                'tokenId' => $this->tokenIdInput->toEncodable(),
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "amount" of required type "BigInt!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_negative_amount(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'recipient' => $this->recipient->id,
            'params' => [
                'tokenId' => $this->tokenIdInput->toEncodable(),
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
            'recipient' => SS58Address::encode($this->recipient->id),
            'params' => [
                'tokenId' => $this->tokenIdInput->toEncodable(),
                'amount' => 0,
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['params.amount' => ['The params.amount is too small, the minimum value it can be is 1.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_greater_than_balance(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'recipient' => SS58Address::encode($this->recipient->id),
            'params' => [
                'tokenId' => $this->tokenIdInput->toEncodable(),
                'amount' => fake()->numberBetween($this->tokenAccount->balance),
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['params.amount' => ['The params.amount is invalid, the amount provided is bigger than the token account balance.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_greater_than_balance_from_signing_wallet(): void
    {
        $signingWallet = Account::factory([
            'id' => app(Generator::class)->public_key(),
            'managed' => true,
        ])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'recipient' => SS58Address::encode($this->recipient->id),
            'params' => [
                'tokenId' => $this->tokenIdInput->toEncodable(),
                'amount' => fake()->numberBetween($this->tokenAccount->balance),
            ],
            'signingAccount' => SS58Address::encode($signingWallet->public_key),
        ], true);

        $this->assertArrayContainsArray(
            ['params.amount' => ['The params.amount is invalid, the amount provided is bigger than the token account balance.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_invalid_keep_alive(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'recipient' => $this->recipient->id,
            'params' => [
                'tokenId' => $this->tokenIdInput->toEncodable(),
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

    public function test_it_will_fail_invalid_signing_wallet(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'recipient' => SS58Address::encode($this->recipient->id),
            'params' => [
                'tokenId' => $this->tokenIdInput->toEncodable(),
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
            ],
            'signingAccount' => 'invalid',
        ], true);

        $this->assertArrayContainsArray(
            ['signingAccount' => ['The signing account is not a valid substrate account.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_no_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'recipient' => $this->recipient->id,
        ], true);

        $this->assertStringContainsString(
            'Variable "$params" of required type "SimpleTransferParams!" was not provided.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_null_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'recipient' => $this->recipient->id,
            'params' => null,
        ], true);

        $this->assertStringContainsString(
            'Variable "$params" of non-null type "SimpleTransferParams!" must not be null.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_empty_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'recipient' => $this->recipient->id,
            'params' => [],
        ], true);

        $this->assertStringContainsString(
            'Variable "$params" got invalid value []; Field "tokenId" of required type "EncodableTokenIdInput!',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
