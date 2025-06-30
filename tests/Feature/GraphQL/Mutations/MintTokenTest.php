<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\MintTokenMutation;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Indexer\Token;
use Enjin\Platform\Models\Substrate\MintParams;
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

class MintTokenTest extends TestCaseGraphQL
{
    use MocksHttpClient;

    protected string $method = 'MintToken';
    protected Codec $codec;
    protected Collection $collection;
    protected Token $token;
    protected Encoder $tokenIdEncoder;
    protected Wallet $recipient;
    protected Wallet $wallet;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->codec = new Codec();
        $this->wallet = Account::daemon();
        $this->collection = Collection::factory()->create(['owner_wallet_id' => $this->wallet]);
        $this->token = Token::factory()->create(['collection_id' => $this->collection]);
        $this->tokenIdEncoder = new Integer($this->token->token_chain_id);
        $this->recipient = Wallet::factory()->create();
    }

    // Happy Path

    public function test_it_can_skip_validation(): void
    {
        $encodedData = TransactionSerializer::encode('Mint', MintTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->public_key,
            collectionId: $collectionId = $this->collection->collection_chain_id,
            mintTokenParams: $params = new MintParams(
                tokenId: $tokenId = random_int(1, 1000),
                amount: fake()->numberBetween(),
            ),
        ));

        $params = $params->toArray()['Mint'];
        $params['tokenId'] = ['integer' => $tokenId];

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($recipient),
            'collectionId' => $collectionId,
            'params' => $params,
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

    public function test_it_can_simulate(): void
    {
        $encodedData = TransactionSerializer::encode('Mint', MintTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->public_key,
            collectionId: $collectionId = $this->collection->collection_chain_id,
            mintTokenParams: $params = new MintParams(
                tokenId: $this->tokenIdEncoder->encode(),
                amount: fake()->numberBetween(),
            ),
        ));

        $params = $params->toArray()['Mint'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

        $this->mockFee($feeDetails = app(Generator::class)->fee_details());
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($recipient),
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
        $token = Token::factory([
            'collection_id' => $collection = Collection::factory()->create(['owner_wallet_id' => Wallet::factory()->create()]),
        ])->create();
        $response = $this->graphql($this->method, $params = [
            'recipient' => SS58Address::encode($this->recipient->public_key),
            'collectionId' => $collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_chain_id),
                'amount' => fake()->numberBetween(1, 10),
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

    public function test_can_mint_a_token_without_unit_price(): void
    {
        $encodedData = TransactionSerializer::encode('Mint', MintTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->public_key,
            collectionId: $collectionId = $this->collection->collection_chain_id,
            mintTokenParams: $params = new MintParams(
                tokenId: $this->tokenIdEncoder->encode(),
                amount: fake()->numberBetween(),
            ),
        ));

        $params = $params->toArray()['Mint'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($recipient),
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

    public function test_can_mint_a_token_with_ss58_signing_account(): void
    {
        $signingWallet = Wallet::factory([
            'public_key' => $signingAccount = app(Generator::class)->public_key(),
        ])->create();
        $token = Token::factory([
            'collection_id' => $collection = Collection::factory(['owner_wallet_id' => $signingWallet])->create(),
        ])->create();
        $encodedData = TransactionSerializer::encode('Mint', MintTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->public_key,
            collectionId: $collectionId = $collection->collection_chain_id,
            mintTokenParams: $params = new MintParams(
                tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
                amount: fake()->numberBetween(),
            ),
        ));

        $params = $params->toArray()['Mint'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($token->token_chain_id);

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($recipient),
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

    public function test_can_mint_a_token_with_public_key_signing_account(): void
    {
        $signingWallet = Wallet::factory([
            'public_key' => $signingAccount = app(Generator::class)->public_key(),
        ])->create();
        $token = Token::factory([
            'collection_id' => $collection = Collection::factory(['owner_wallet_id' => $signingWallet])->create(),
        ])->create();

        $encodedData = TransactionSerializer::encode('Mint', MintTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->public_key,
            collectionId: $collectionId = $collection->collection_chain_id,
            mintTokenParams: $params = new MintParams(
                tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
                amount: fake()->numberBetween(),
            ),
        ));

        $params = $params->toArray()['Mint'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($token->token_chain_id);

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($recipient),
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

    public function test_can_mint_a_token_with_different_types(): void
    {
        $encodedData = TransactionSerializer::encode('Mint', MintTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->public_key,
            collectionId: $collectionId = $this->collection->collection_chain_id,
            mintTokenParams: new MintParams(
                tokenId: $this->tokenIdEncoder->encode(),
                amount: $amount = fake()->numberBetween(),
            ),
        ));

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($recipient),
            'collectionId' => (int) $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'amount' => (string) $amount,
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

    public function test_can_mint_a_token_with_bigint_collection_id_and_token_id(): void
    {
        Collection::where('collection_chain_id', Hex::MAX_UINT128)->delete();

        $collection = Collection::factory([
            'collection_chain_id' => Hex::MAX_UINT128,
            'owner_wallet_id' => $this->wallet,
        ])->create();

        $token = Token::factory([
            'collection_id' => $collection->id,
            'token_chain_id' => Hex::MAX_UINT128,
        ])->create();

        $encodedData = TransactionSerializer::encode('Mint', MintTokenMutation::getEncodableParams(
            recipientAccount: $recipient = $this->recipient->public_key,
            collectionId: $collectionId = $collection->collection_chain_id,
            mintTokenParams: $params = new MintParams(
                tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
                amount: fake()->numberBetween(),
            ),
        ));

        $params = $params->toArray()['Mint'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($token->token_chain_id);

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

    public function test_can_mint_a_token_with_not_existent_recipient_and_creates_it(): void
    {
        Wallet::where('public_key', '=', $recipient = app(Generator::class)->public_key())?->delete();

        $encodedData = TransactionSerializer::encode('Mint', MintTokenMutation::getEncodableParams(
            recipientAccount: $recipient,
            collectionId: $collectionId = $this->collection->collection_chain_id,
            mintTokenParams: $params = new MintParams(
                tokenId: $this->tokenIdEncoder->encode(),
                amount: fake()->numberBetween(),
            ),
        ));

        $params = $params->toArray()['Mint'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

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

    public function test_it_will_fail_with_invalid_recipient(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => 'not_substrate_address',
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'amount' => fake()->numberBetween(),
            ],
        ], true);

        $this->assertArrayContainsArray(
            ['recipient' => ['The recipient is not a valid substrate account.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_collection_id_doesnt_exists(): void
    {
        Collection::where('collection_chain_id', '=', $collectionId = fake()->numberBetween(2000))?->delete();

        $response = $this->graphql($this->method, [
            'recipient' => $this->recipient->public_key,
            'collectionId' => $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'amount' => fake()->numberBetween(),
            ],
        ], true);

        $this->assertStringContainsString(
            'The selected collection id is invalid.',
            $response['error']['collectionId'][0]
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_negative_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => $this->recipient->public_key,
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => -1,
                'amount' => fake()->numberBetween(),
            ],
        ], true);

        $this->assertStringContainsString(
            'Variable "$params" got invalid value -1 at "params.tokenId"; Expected type "EncodableTokenIdInput" to be an object',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_overflow_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => $this->recipient->public_key,
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(Hex::MAX_UINT256),
                'amount' => fake()->numberBetween(1),
            ],
        ], true);

        $this->assertStringContainsString(
            'The integer is too large, the maximum value it can be is',
            $response['errors']['integer'][0]
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_negative_amount(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => $this->recipient->public_key,
            'collectionId' => $this->collection->collection_chain_id,
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

    public function test_it_will_fail_with_zero_amount(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => $this->recipient->public_key,
            'collectionId' => $this->collection->collection_chain_id,
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

    public function test_it_will_fail_with_overflow_amount(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => $this->recipient->public_key,
            'collectionId' => $this->collection->collection_chain_id,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'amount' => Hex::MAX_UINT256,
            ],
        ], true);

        $this->assertStringContainsString(
            'The params.amount is too large, the maximum value it can be is 340282366920938463463374607431768211455.',
            $response['error']['params.amount'][0]
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
