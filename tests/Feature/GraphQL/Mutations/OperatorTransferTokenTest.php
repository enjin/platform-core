<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\OperatorTransferTokenMutation;
use Enjin\Platform\Models\Indexer\Account;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Indexer\CollectionAccount;
use Enjin\Platform\Models\Indexer\Token;
use Enjin\Platform\Models\Indexer\TokenAccount;
use Enjin\Platform\Models\Substrate\OperatorTransferParams;
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

class OperatorTransferTokenTest extends TestCaseGraphQL
{
    use MocksHttpClient;

    protected string $method = 'OperatorTransferToken';
    protected Codec $codec;

    protected Account $wallet;
    protected Collection $collection;
    protected CollectionAccount $collectionAccount;
    protected Token $token;
    protected Encoder $tokenIdEncoder;
    protected TokenAccount $tokenAccount;
    protected Account $recipient;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->codec = new Codec();
        $this->wallet = $this->getDaemonAccount();
        $this->recipient = Account::factory()->create();
        $this->collection = Collection::factory()->create(['owner_id' => $this->wallet]);
        $this->token = Token::factory(['collection_id' => $this->collection->id])->create();
        $this->tokenAccount = TokenAccount::factory([
            'collection_id' => $this->collection,
            'token_id' => $this->token,
            'account_id' => $this->wallet,
        ])->create();
        $this->collectionAccount = CollectionAccount::factory([
            'collection_id' => $this->collection,
            'account_id' => $this->wallet,
            'account_count' => 1,
        ])->create();
        $this->tokenIdEncoder = new Integer($this->token->token_id);
    }

    // Happy Path
    public function test_it_can_skip_validation(): void
    {
        $signingWallet = Account::factory([
            'managed' => false,
        ])->create();

        $encodedData = TransactionSerializer::encode('Transfer', OperatorTransferTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->id,
            collectionId: $collectionId = $this->collection->id,
            operatorTransferParams: $params = new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode(),
                source: $this->wallet->id,
                amount: fake()->numberBetween(1, $this->tokenAccount->balance),
            ),
        ));

        $params = $params->toArray()['Operator'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

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
        $encodedData = TransactionSerializer::encode('Transfer', OperatorTransferTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->id,
            collectionId: $collectionId = $this->collection->id,
            operatorTransferParams: $params = new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode(),
                source: $this->wallet->id,
                amount: fake()->numberBetween(1, $this->tokenAccount->balance),
            ),
        ));

        $params = $params->toArray()['Operator'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

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
        $encodedData = TransactionSerializer::encode('Transfer', OperatorTransferTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->id,
            collectionId: $collectionId = $this->collection->id,
            operatorTransferParams: $params = new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode(),
                source: $this->wallet->id,
                amount: fake()->numberBetween(1, $this->tokenAccount->balance),
            ),
        ));

        $params = $params->toArray()['Operator'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

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

        CollectionAccount::factory([
            'collection_id' => $collection,
            'account_id' => $signingWallet,
            'account_count' => 1,
        ])->create();

        $token = Token::factory([
            'collection_id' => $collection,
        ])->create();

        $tokenAccount = TokenAccount::factory([
            'collection_id' => $collection,
            'token_id' => $token,
            'account_id' => $signingWallet,
        ])->create();

        $response = $this->graphql($this->method, $params = [
            'collectionId' => $collection->id,
            'recipient' => SS58Address::encode($this->recipient->id),
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_id),
                'source' => $this->wallet->id,
                'amount' => fake()->numberBetween(1, $tokenAccount->balance),
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
        $encodedData = TransactionSerializer::encode('Transfer', OperatorTransferTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->id,
            collectionId: $collectionId = $this->collection->id,
            operatorTransferParams: $params = new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode(),
                source: $this->wallet->id,
                amount: fake()->numberBetween(1, $this->tokenAccount->balance),
            ),
        ));

        $params = $params->toArray()['Operator'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

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

    public function test_it_can_transfer_token_with_ss58_signing_account(): void
    {
        $signingWallet = Account::factory([
            'id' => $signingAccount = app(Generator::class)->public_key(),
        ])->create();
        $collection = Collection::factory(['owner_id' => $signingWallet])->create();
        CollectionAccount::factory([
            'collection_id' => $collection,
            'account_id' => $signingWallet,
            'account_count' => 1,
        ])->create();
        $token = Token::factory([
            'collection_id' => $collection,
        ])->create();
        $tokenAccount = TokenAccount::factory([
            'collection_id' => $collection,
            'token_id' => $token,
            'account_id' => $signingWallet,
        ])->create();

        $encodedData = TransactionSerializer::encode('Transfer', OperatorTransferTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->id,
            collectionId: $collectionId = $collection->id,
            operatorTransferParams: $params = new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode($token->token_id),
                source: $signingWallet->id,
                amount: fake()->numberBetween(1, $tokenAccount->balance),
            ),
        ));

        $params = $params->toArray()['Operator'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($token->token_id);

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($recipient),
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

    public function test_it_can_transfer_token_with_public_key_signing_account(): void
    {
        $signingWallet = Account::factory([
            'id' => $signingAccount = app(Generator::class)->public_key(),
        ])->create();
        $collection = Collection::factory(['owner_id' => $signingWallet])->create();
        CollectionAccount::factory([
            'collection_id' => $collection,
            'account_id' => $signingWallet,
            'account_count' => 1,
        ])->create();
        $token = Token::factory([
            'collection_id' => $collection,
        ])->create();
        $tokenAccount = TokenAccount::factory([
            'collection_id' => $collection,
            'token_id' => $token,
            'account_id' => $signingWallet,
        ])->create();
        $encodedData = TransactionSerializer::encode('Transfer', OperatorTransferTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->id,
            collectionId: $collectionId = $collection->id,
            operatorTransferParams: $params = new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode($token->token_id),
                source: $signingWallet->id,
                amount: fake()->numberBetween(1, $tokenAccount->balance),
            ),
        ));

        $params = $params->toArray()['Operator'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($token->token_id);


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

    public function test_it_can_transfer_token_without_pass_keep_alive(): void
    {
        $encodedData = TransactionSerializer::encode('Transfer', OperatorTransferTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->id,
            collectionId: $collectionId = $this->collection->id,
            operatorTransferParams: $params = new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode(),
                source: $this->wallet->id,
                amount: fake()->numberBetween(1, $this->tokenAccount->balance),
            ),
        ));

        $params = $params->toArray()['Operator'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

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
            'managed' => true,
        ])->create();
        $collection = Collection::factory(['owner_id' => $signingWallet])->create();
        CollectionAccount::factory([
            'collection_id' => $collection,
            'account_id' => $signingWallet,
            'account_count' => 1,
        ])->create();
        $token = Token::factory([
            'collection_id' => $collection,
        ])->create();
        $tokenAccount = TokenAccount::factory([
            'collection_id' => $collection,
            'token_id' => $token,
            'account_id' => $signingWallet,
        ])->create();

        $encodedData = TransactionSerializer::encode('Transfer', OperatorTransferTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->id,
            collectionId: $collectionId = $collection->id,
            operatorTransferParams: $params = new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode($token->token_id),
                source: $signingWallet->id,
                amount: fake()->numberBetween(1, $tokenAccount->balance),
            ),
        ));

        $params = $params->toArray()['Operator'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($token->token_id);

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

    public function test_it_can_transfer_token_with_null_signing_wallet(): void
    {
        $encodedData = TransactionSerializer::encode('Transfer', OperatorTransferTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->id,
            collectionId: $collectionId = $this->collection->id,
            operatorTransferParams: $params = new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode(),
                source: $this->wallet->id,
                amount: fake()->numberBetween(1, $this->tokenAccount->balance),
            ),
        ));

        $params = $params->toArray()['Operator'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

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

    public function test_it_can_transfer_token_with_empty_signing_wallet_and_works_as_daemon(): void
    {
        $encodedData = TransactionSerializer::encode('Transfer', OperatorTransferTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->id,
            collectionId: $collectionId = $this->collection->id,
            operatorTransferParams: $params = new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode(),
                source: $this->wallet->id,
                amount: fake()->numberBetween(1, $this->tokenAccount->balance),
            ),
        ));

        $params = $params->toArray()['Operator'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

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
        Account::where('id', '=', $address = app(Generator::class)->public_key())?->delete();

        $encodedData = TransactionSerializer::encode('Transfer', OperatorTransferTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $address,
            collectionId: $collectionId = $this->collection->id,
            operatorTransferParams: $params = new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode(),
                source: $this->wallet->id,
                amount: fake()->numberBetween(1, $this->tokenAccount->balance),
                operatorPaysDeposit: fake()->boolean(),
            ),
        ));

        $params = $params->toArray()['Operator'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

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
            'id' => $address,
        ]);

        Event::assertDispatched(TransactionCreated::class);
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

        $encodedData = TransactionSerializer::encode('Transfer', OperatorTransferTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->id,
            collectionId: $collectionId = $collection->id,
            operatorTransferParams: $params = new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode($token->token_id),
                source: $this->wallet->id,
                amount: fake()->numberBetween(1, $tokenAccount->balance),
            ),
        ));

        $params = $params->toArray()['Operator'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($token->token_id);

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

        $encodedData = TransactionSerializer::encode('Transfer', OperatorTransferTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->id,
            collectionId: $collectionId = $collection->id,
            operatorTransferParams: $params = new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode($token->token_id),
                source: $this->wallet->id,
                amount: fake()->numberBetween(1, $tokenAccount->balance),
            ),
        ));

        $params = $params->toArray()['Operator'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($token->token_id);

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
        $this->deleteAllFrom($collectionId = fake()->numberBetween());

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($this->recipient->id),
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => SS58Address::encode($this->wallet->id),
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
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => $this->wallet->id,
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
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => $this->wallet->id,
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
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => $this->wallet->id,
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
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => SS58Address::encode($this->wallet->id),
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
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => $this->wallet->id,
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
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => $this->wallet->id,
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
                'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
                'source' => $this->wallet->id,
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
                'source' => $this->wallet->id,
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
                'source' => $this->wallet->id,
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$params" got invalid value null at "params.tokenId"; Expected non-nullable type "EncodableTokenIdInput!" not to be null',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_no_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'recipient' => $this->recipient->id,
            'params' => [
                'source' => $this->wallet->id,
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "tokenId" of required type "EncodableTokenIdInput!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_source(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'recipient' => SS58Address::encode($this->recipient->id),
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => 'invalid',
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['params.source' => ['The params.source is not a valid substrate account.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_source(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'recipient' => SS58Address::encode($this->recipient->id),
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertStringContainsString(
            'Field "source" of required type "String!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_source(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'recipient' => SS58Address::encode($this->recipient->id),
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => null,
                'amount' => fake()->numberBetween(1, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$params" got invalid value null at "params.source"; Expected non-nullable type "String!" not to be null',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_invalid_amount(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'recipient' => SS58Address::encode($this->recipient->id),
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => $this->wallet->id,
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
            'recipient' => SS58Address::encode($this->recipient->id),
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => SS58Address::encode($this->wallet->id),
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
            'recipient' => SS58Address::encode($this->recipient->id),
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => SS58Address::encode($this->wallet->id),
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
            'recipient' => SS58Address::encode($this->recipient->id),
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => SS58Address::encode($this->wallet->id),
                'amount' => -1,
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$params" got invalid value -1 at "params.amount"; Cannot represent following value as uint256',
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
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => SS58Address::encode($this->wallet->id),
                'amount' => fake()->numberBetween($this->tokenAccount->balance),
            ],
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
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => $this->wallet->id,
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
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => SS58Address::encode($this->wallet->id),
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
            'Variable "$params" of required type "OperatorTransferParams!" was not provided.',
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
            'Variable "$params" of non-null type "OperatorTransferParams!" must not be null.',
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
            'Field "tokenId" of required type "EncodableTokenIdInput!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
