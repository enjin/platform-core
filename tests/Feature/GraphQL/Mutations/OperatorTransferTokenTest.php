<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\CollectionAccount;
use Enjin\Platform\Models\Substrate\OperatorTransferParams;
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
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

class OperatorTransferTokenTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;
    use MocksWebsocketClient;

    protected string $method = 'OperatorTransferToken';
    protected Codec $codec;
    protected string $defaultAccount;
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
        $this->defaultAccount = Account::daemonPublicKey();

        $this->wallet = Wallet::factory()->create();
        $this->recipient = Wallet::factory()->create();
        $this->tokenAccount = TokenAccount::factory([
            'wallet_id' => $this->wallet,
        ])->create();
        $this->token = Token::find($this->tokenAccount->token_id);
        $this->tokenIdEncoder = new Integer($this->token->token_chain_id);
        $this->collection = Collection::find($this->tokenAccount->collection_id);
        $this->collectionAccount = CollectionAccount::factory([
            'collection_id' => $this->collection,
            'wallet_id' => $this->wallet,
            'account_count' => 1,
        ])->create();
    }

    // Happy Path
    public function test_it_can_skip_validation(): void
    {
        $signingWallet = Wallet::factory([
            'managed' => false,
        ])->create();

        $encodedData = $this->codec->encode()->transferToken(
            $recipient = $this->recipient->public_key,
            $collectionId = $this->collection->collection_chain_id,
            $params = new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode(),
                source: $this->wallet->public_key,
                amount: fake()->numberBetween(0, $this->tokenAccount->balance),
                keepAlive: fake()->boolean(),
            ),
        );

        $params = $params->toArray()['Operator'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($recipient),
            'params' => $params,
            'signingAccount' => SS58Address::encode($signingWallet->public_key),
            'skipValidation' => true,
        ]);

        $this->assertArraySubset([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => [
                'account' => [
                    'publicKey' => $signingWallet->public_key,
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
        $encodedData = $this->codec->encode()->transferToken(
            $recipient = $this->recipient->public_key,
            $collectionId = $this->collection->collection_chain_id,
            $params = new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode(),
                source: $this->wallet->public_key,
                amount: fake()->numberBetween(0, $this->tokenAccount->balance),
                keepAlive: fake()->boolean(),
            ),
        );

        $params = $params->toArray()['Operator'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($recipient),
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
    }

    public function test_it_can_simulate(): void
    {
        $encodedData = $this->codec->encode()->transferToken(
            $recipient = $this->recipient->public_key,
            $collectionId = $this->collection->collection_chain_id,
            $params = new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode(),
                source: $this->wallet->public_key,
                amount: fake()->numberBetween(0, $this->tokenAccount->balance),
                keepAlive: fake()->boolean(),
            ),
        );

        $params = $params->toArray()['Operator'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

        $this->mockFee($feeDetails = app(Generator::class)->fee_details());
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($recipient),
            'params' => $params,
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

    public function test_it_can_transfer_token(): void
    {
        $encodedData = $this->codec->encode()->transferToken(
            $recipient = $this->recipient->public_key,
            $collectionId = $this->collection->collection_chain_id,
            $params = new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode(),
                source: $this->wallet->public_key,
                amount: fake()->numberBetween(0, $this->tokenAccount->balance),
                keepAlive: fake()->boolean(),
            ),
        );

        $params = $params->toArray()['Operator'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($recipient),
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

    public function test_it_can_transfer_token_without_pass_keep_alive(): void
    {
        $encodedData = $this->codec->encode()->transferToken(
            $recipient = $this->recipient->public_key,
            $collectionId = $this->collection->collection_chain_id,
            $params = new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode(),
                source: $this->wallet->public_key,
                amount: fake()->numberBetween(0, $this->tokenAccount->balance),
            ),
        );

        $params = $params->toArray()['Operator'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($recipient),
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

    public function test_it_can_transfer_token_with_signing_wallet(): void
    {
        $signingWallet = Wallet::factory([
            'managed' => true,
        ])->create();

        $encodedData = $this->codec->encode()->transferToken(
            $recipient = $this->recipient->public_key,
            $collectionId = $this->collection->collection_chain_id,
            $params = new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode(),
                source: $this->wallet->public_key,
                amount: fake()->numberBetween(0, $this->tokenAccount->balance),
                keepAlive: fake()->boolean(),
            ),
        );

        $params = $params->toArray()['Operator'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($recipient),
            'params' => $params,
            'signingAccount' => SS58Address::encode($signingWallet->public_key),
        ]);

        $this->assertArraySubset([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => [
                'account' => [
                    'publicKey' => $signingWallet->public_key,
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
        $encodedData = $this->codec->encode()->transferToken(
            $recipient = $this->recipient->public_key,
            $collectionId = $this->collection->collection_chain_id,
            $params = new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode(),
                source: $this->wallet->public_key,
                amount: fake()->numberBetween(0, $this->tokenAccount->balance),
                keepAlive: fake()->boolean(),
            ),
        );

        $params = $params->toArray()['Operator'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($recipient),
            'params' => $params,
            'signingAccount' => null,
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

    public function test_it_can_transfer_token_with_empty_signing_wallet_and_works_as_daemon(): void
    {
        $encodedData = $this->codec->encode()->transferToken(
            $recipient = $this->recipient->public_key,
            $collectionId = $this->collection->collection_chain_id,
            $params = new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode(),
                source: $this->wallet->public_key,
                amount: fake()->numberBetween(0, $this->tokenAccount->balance),
                keepAlive: fake()->boolean(),
            ),
        );

        $params = $params->toArray()['Operator'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($recipient),
            'params' => $params,
            'signingAccount' => '',
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

    public function test_it_can_transfer_token_with_recipient_that_doesnt_exists(): void
    {
        Wallet::where('public_key', '=', $address = app(Generator::class)->public_key())?->delete();

        $encodedData = $this->codec->encode()->transferToken(
            $recipient = $address,
            $collectionId = $this->collection->collection_chain_id,
            $params = new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode(),
                source: $this->wallet->public_key,
                amount: fake()->numberBetween(0, $this->tokenAccount->balance),
                keepAlive: fake()->boolean(),
            ),
        );

        $params = $params->toArray()['Operator'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($recipient),
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

        $this->assertDatabaseHas('wallets', [
            'public_key' => $address,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_transfer_token_with_bigint_collection_id(): void
    {
        Collection::where('collection_chain_id', Hex::MAX_UINT128)->update(['collection_chain_id' => random_int(1, 1000)]);
        $collection = Collection::factory([
            'collection_chain_id' => Hex::MAX_UINT128,
        ])->create();
        CollectionAccount::factory([
            'collection_id' => $collection,
            'wallet_id' => $this->wallet,
            'account_count' => 1,
        ])->create();
        $token = Token::factory([
            'collection_id' => $collection,
        ])->create();
        $tokenAccount = TokenAccount::factory([
            'collection_id' => $collection,
            'token_id' => $token,
            'wallet_id' => $this->wallet,
        ])->create();

        $encodedData = $this->codec->encode()->transferToken(
            $recipient = $this->recipient->public_key,
            $collectionId = $collection->collection_chain_id,
            $params = new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
                source: $this->wallet->public_key,
                amount: fake()->numberBetween(0, $tokenAccount->balance),
                keepAlive: fake()->boolean(),
            ),
        );

        $params = $params->toArray()['Operator'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($token->token_chain_id);

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($recipient),
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

    public function test_it_can_transfer_token_with_bigint_token_id(): void
    {
        $collection = Collection::factory()->create();
        CollectionAccount::factory([
            'collection_id' => $collection,
            'wallet_id' => $this->wallet,
            'account_count' => 1,
        ])->create();
        Token::where('token_chain_id', Hex::MAX_UINT128)->update(['token_chain_id' => random_int(1, 1000)]);
        $token = Token::factory([
            'collection_id' => $collection,
            'token_chain_id' => Hex::MAX_UINT128,
        ])->create();
        $tokenAccount = TokenAccount::factory([
            'collection_id' => $collection,
            'token_id' => $token,
            'wallet_id' => $this->wallet,
        ])->create();

        $encodedData = $this->codec->encode()->transferToken(
            $recipient = $this->recipient->public_key,
            $collectionId = $collection->collection_chain_id,
            $params = new OperatorTransferParams(
                tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
                source: $this->wallet->public_key,
                amount: fake()->numberBetween(0, $tokenAccount->balance),
                keepAlive: fake()->boolean(),
            ),
        );

        $params = $params->toArray()['Operator'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($token->token_chain_id);

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($recipient),
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

    // Exception Path

    public function test_it_will_fail_collection_id_doesnt_exists(): void
    {
        Collection::where('collection_chain_id', '=', $collectionId = fake()->numberBetween(2000))?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => SS58Address::encode($this->wallet->public_key),
                'amount' => fake()->numberBetween(0, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertArraySubset(
            ['collectionId' => ['The selected collection id is invalid.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_invalid_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => 'not_valid',
            'recipient' => $this->recipient->public_key,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => $this->wallet->public_key,
                'amount' => fake()->numberBetween(0, $this->tokenAccount->balance),
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
            'recipient' => $this->recipient->public_key,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => $this->wallet->public_key,
                'amount' => fake()->numberBetween(0, $this->tokenAccount->balance),
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
            'recipient' => $this->recipient->public_key,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => $this->wallet->public_key,
                'amount' => fake()->numberBetween(0, $this->tokenAccount->balance),
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
            'collectionId' => $this->collection->collection_chain_id,
            'recipient' => 'not_valid',
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => SS58Address::encode($this->wallet->public_key),
                'amount' => fake()->numberBetween(0, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertArraySubset(
            ['recipient' => ['The recipient is not a valid substrate account.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_null_recipient(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipient' => null,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => $this->wallet->public_key,
                'amount' => fake()->numberBetween(0, $this->tokenAccount->balance),
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
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => $this->wallet->public_key,
                'amount' => fake()->numberBetween(0, $this->tokenAccount->balance),
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
        Token::where('token_chain_id', '=', $tokenId = fake()->numberBetween())?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
                'source' => $this->wallet->public_key,
                'amount' => fake()->numberBetween(0, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertArraySubset(
            ['params.tokenId' => ['The params.token id does not exist in the specified collection.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_invalid_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipient' => $this->recipient->public_key,
            'params' => [
                'tokenId' => 'not_valid',
                'source' => $this->wallet->public_key,
                'amount' => fake()->numberBetween(0, $this->tokenAccount->balance),
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
            'collectionId' => $this->collection->collection_chain_id,
            'recipient' => $this->recipient->public_key,
            'params' => [
                'tokenId' => null,
                'source' => $this->wallet->public_key,
                'amount' => fake()->numberBetween(0, $this->tokenAccount->balance),
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
            'collectionId' => $this->collection->collection_chain_id,
            'recipient' => $this->recipient->public_key,
            'params' => [
                'source' => $this->wallet->public_key,
                'amount' => fake()->numberBetween(0, $this->tokenAccount->balance),
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
            'collectionId' => $this->collection->collection_chain_id,
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => 'invalid',
                'amount' => fake()->numberBetween(0, $this->tokenAccount->balance),
            ],
        ], true);

        $this->assertArraySubset(
            ['params.source' => ['The params.source is not a valid substrate account.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_source(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'amount' => fake()->numberBetween(0, $this->tokenAccount->balance),
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
            'collectionId' => $this->collection->collection_chain_id,
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => null,
                'amount' => fake()->numberBetween(0, $this->tokenAccount->balance),
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
            'collectionId' => $this->collection->collection_chain_id,
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => $this->wallet->public_key,
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
            'collectionId' => $this->collection->collection_chain_id,
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => SS58Address::encode($this->wallet->public_key),
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
            'collectionId' => $this->collection->collection_chain_id,
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => SS58Address::encode($this->wallet->public_key),
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
            'collectionId' => $this->collection->collection_chain_id,
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => SS58Address::encode($this->wallet->public_key),
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
            'collectionId' => $this->collection->collection_chain_id,
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => SS58Address::encode($this->wallet->public_key),
                'amount' => 0,
            ],
        ], true);

        $this->assertArraySubset(
            ['params.amount' => ['The params.amount is too small, the minimum value it can be is 1.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_greater_than_balance(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => SS58Address::encode($this->wallet->public_key),
                'amount' => fake()->numberBetween($this->tokenAccount->balance),
            ],
        ], true);

        $this->assertArraySubset(
            ['params.amount' => ['The params.amount is invalid, the amount provided is bigger than the token account balance.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_invalid_keep_alive(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipient' => $this->recipient->public_key,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => $this->wallet->public_key,
                'amount' => fake()->numberBetween(0, $this->tokenAccount->balance),
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
            'collectionId' => $this->collection->collection_chain_id,
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'source' => SS58Address::encode($this->wallet->public_key),
                'amount' => fake()->numberBetween(0, $this->tokenAccount->balance),
            ],
            'signingAccount' => 'invalid',
        ], true);

        $this->assertArraySubset(
            ['signingAccount' => ['The signing account is not a valid substrate account.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_no_params(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'recipient' => $this->recipient->public_key,
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
            'collectionId' => $this->collection->collection_chain_id,
            'recipient' => $this->recipient->public_key,
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
            'collectionId' => $this->collection->collection_chain_id,
            'recipient' => $this->recipient->public_key,
            'params' => [],
        ], true);

        $this->assertStringContainsString(
            'Field "tokenId" of required type "EncodableTokenIdInput!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
