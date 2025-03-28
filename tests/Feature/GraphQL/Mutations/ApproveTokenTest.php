<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\ApproveTokenMutation;
use Enjin\Platform\Models\Block;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Models\TokenAccount;
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
use Facades\Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

class ApproveTokenTest extends TestCaseGraphQL
{
    use MocksHttpClient;

    protected $method = 'ApproveToken';

    protected Codec $codec;
    protected Model $wallet;
    protected Model $collection;
    protected Model $token;
    protected Model $tokenAccount;
    protected Encoder $tokenIdEncoder;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->codec = new Codec();
        $this->wallet = Account::daemon();
        $this->collection = Collection::factory()->create(['owner_wallet_id' => $this->wallet]);
        $this->token = Token::factory(['collection_id' => $this->collection])->create();
        $this->tokenAccount = TokenAccount::factory([
            'wallet_id' => $this->wallet,
            'collection_id' => $this->collection->id,
            'token_id' => $this->token->id,
        ])->create();
        $this->tokenIdEncoder = new Integer($this->token->token_chain_id);
    }

    // Happy Path
    public function test_it_can_skip_validation(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, ApproveTokenMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            tokenId: $tokenId = random_int(1, 1000),
            operator: $operator = app(Generator::class)->public_key(),
            amount: $amount = $this->tokenAccount->balance,
            currentAmount: $currentAmount = $this->tokenAccount->balance
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => ['integer' => $tokenId],
            'amount' => $amount,
            'currentAmount' => $currentAmount,
            'operator' => SS58Address::encode($operator),
            'skipValidation' => true,
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

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_bypass_ownership(): void
    {
        $token = Token::factory([
            'collection_id' => $collection = Collection::factory()->create(['owner_wallet_id' => Wallet::factory()->create()]),
        ])->create();

        $response = $this->graphql($this->method, $params = [
            'collectionId' => $collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_chain_id),
            'amount' => fake()->numberBetween(1, 10),
            'currentAmount' => fake()->numberBetween(1, 10),
            'operator' => fake()->text(),
        ], true);

        $this->assertEquals(
            [
                'collectionId' => ['The collection id provided is not owned by you.'],
                'operator' => ['The operator is not a valid substrate account.'],
            ],
            $response['error']
        );

        IsCollectionOwner::bypass();
        $response = $this->graphql($this->method, $params, true);
        $this->assertEquals(
            ['operator' => ['The operator is not a valid substrate account.']],
            $response['error']
        );
        IsCollectionOwner::unBypass();
    }

    /**
     * It can approve token using encodeTokenId.
     */
    public function test_it_can_approve_a_token_with_any_operator_using_adapter(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, ApproveTokenMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode(),
            operator: $operator = app(Generator::class)->public_key(),
            amount: $amount = $this->tokenAccount->balance,
            currentAmount: $currentAmount = $this->tokenAccount->balance
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'amount' => $amount,
            'currentAmount' => $currentAmount,
            'operator' => SS58Address::encode($operator),
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
        $encodedData = TransactionSerializer::encode($this->method, ApproveTokenMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode(),
            operator: $operator = app(Generator::class)->public_key(),
            amount: $amount = $this->tokenAccount->balance,
            currentAmount: $currentAmount = $this->tokenAccount->balance
        ));

        $this->mockFee($feeDetails = app(Generator::class)->fee_details());
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'amount' => $amount,
            'currentAmount' => $currentAmount,
            'operator' => SS58Address::encode($operator),
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

    public function test_it_can_approve_a_token_with_any_operator(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, ApproveTokenMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode(),
            operator: $operator = app(Generator::class)->public_key(),
            amount: $amount = $this->tokenAccount->balance,
            currentAmount: $currentAmount = $this->tokenAccount->balance
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'amount' => $amount,
            'currentAmount' => $currentAmount,
            'operator' => SS58Address::encode($operator),
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

    public function test_it_can_approve_a_token_with_ss58_signing_account(): void
    {
        $newOwner = Wallet::factory()->create([
            'public_key' => $signingAccount = app(Generator::class)->public_key(),
        ]);
        $newToken = Token::factory([
            'collection_id' => $collection = Collection::factory(['owner_wallet_id' => $newOwner])->create(),
        ])->create();
        $encodedData = TransactionSerializer::encode($this->method, ApproveTokenMutation::getEncodableParams(
            collectionId: $collectionId = $collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode($newToken->token_chain_id),
            operator: $operator = app(Generator::class)->public_key(),
            amount: $amount = $this->tokenAccount->balance,
            currentAmount: $currentAmount = $this->tokenAccount->balance,
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($newToken->token_chain_id),
            'amount' => $amount,
            'currentAmount' => $currentAmount,
            'operator' => SS58Address::encode($operator),
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

    public function test_it_can_approve_a_token_with_public_key_signing_account(): void
    {
        $newOwner = Wallet::factory()->create([
            'public_key' => $signingAccount = app(Generator::class)->public_key(),
        ]);
        $newToken = Token::factory([
            'collection_id' => $collection = Collection::factory(['owner_wallet_id' => $newOwner])->create(),
        ])->create();
        $encodedData = TransactionSerializer::encode($this->method, ApproveTokenMutation::getEncodableParams(
            collectionId: $collectionId = $collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode($newToken->token_chain_id),
            operator: $operator = app(Generator::class)->public_key(),
            amount: $amount = $this->tokenAccount->balance,
            currentAmount: $currentAmount = $this->tokenAccount->balance,
        ));


        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($newToken->token_chain_id),
            'amount' => $amount,
            'currentAmount' => $currentAmount,
            'operator' => SS58Address::encode($operator),
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

    public function test_it_can_approve_a_token_with_operator_doesnt_exist(): void
    {
        Wallet::where('public_key', '=', $operator = app(Generator::class)->public_key())?->delete();

        $encodedData = TransactionSerializer::encode($this->method, ApproveTokenMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode(),
            operator: $operator,
            amount: $amount = $this->tokenAccount->balance,
            currentAmount: $currentAmount = $this->tokenAccount->balance
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'amount' => $amount,
            'currentAmount' => $currentAmount,
            'operator' => SS58Address::encode($operator),
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

    public function test_it_can_approve_a_token_with_operator_does_exist(): void
    {
        Wallet::factory(['public_key' => $operator = app(Generator::class)->public_key()])->create();

        $encodedData = TransactionSerializer::encode($this->method, ApproveTokenMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode(),
            operator: $operator,
            amount: $amount = $this->tokenAccount->balance,
            currentAmount: $currentAmount = $this->tokenAccount->balance
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'amount' => $amount,
            'currentAmount' => $currentAmount,
            'operator' => SS58Address::encode($operator),
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

    public function test_it_can_approve_a_token_with_expiration(): void
    {
        Block::truncate();
        $block = Block::factory()->create();
        $encodedData = TransactionSerializer::encode($this->method, ApproveTokenMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode(),
            operator: $operator = app(Generator::class)->public_key(),
            amount: $amount = $this->tokenAccount->balance,
            currentAmount: $currentAmount = $this->tokenAccount->balance,
            expiration: $expiration = fake()->numberBetween($block->number)
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'amount' => $amount,
            'currentAmount' => $currentAmount,
            'operator' => SS58Address::encode($operator),
            'expiration' => $expiration,
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

    public function test_it_can_approve_a_token_with_big_int_collection_id(): void
    {
        Collection::where('collection_chain_id', Hex::MAX_UINT128)->delete();

        $collection = Collection::factory([
            'collection_chain_id' => Hex::MAX_UINT128,
            'owner_wallet_id' => $this->wallet->id,
        ])->create();
        $token = Token::factory([
            'collection_id' => $collection,
        ])->create();
        $tokenAccount = TokenAccount::factory([
            'wallet_id' => $this->wallet,
            'collection_id' => $collection,
            'token_id' => $token,
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, ApproveTokenMutation::getEncodableParams(
            collectionId: $collectionId = $collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
            operator: $operator = app(Generator::class)->public_key(),
            amount: $amount = $tokenAccount->balance,
            currentAmount: $currentAmount = $tokenAccount->balance
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_chain_id),
            'amount' => $amount,
            'currentAmount' => $currentAmount,
            'operator' => SS58Address::encode($operator),
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

    public function test_it_can_approve_a_token_with_big_int_token_id(): void
    {
        $collection = Collection::factory(['owner_wallet_id' => $this->wallet->id])->create();
        $token = Token::factory([
            'token_chain_id' => Hex::MAX_UINT128,
            'collection_id' => $collection->id,
        ])->create();
        $tokenAccount = TokenAccount::factory([
            'wallet_id' => $this->wallet,
            'collection_id' => $collection,
            'token_id' => $token,
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, ApproveTokenMutation::getEncodableParams(
            collectionId: $collectionId = $collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
            operator: $operator = app(Generator::class)->public_key(),
            amount: $amount = $tokenAccount->balance,
            currentAmount: $currentAmount = $tokenAccount->balance
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_chain_id),
            'amount' => $amount,
            'currentAmount' => $currentAmount,
            'operator' => SS58Address::encode($operator),
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

    public function test_it_can_approve_a_token_with_big_int_amount(): void
    {
        $collection = Collection::factory(['owner_wallet_id' => $this->wallet->id])->create();
        $token = Token::factory(['collection_id' => $collection->id])->create();
        $tokenAccount = TokenAccount::factory([
            'collection_id' => $collection->id,
            'token_id' => $token->id,
            'wallet_id' => $this->wallet,
            'balance' => Hex::MAX_UINT128,
        ])->create();

        $collection = Collection::find($tokenAccount->collection_id);
        $token = Token::find($tokenAccount->token_id);

        $encodedData = TransactionSerializer::encode($this->method, ApproveTokenMutation::getEncodableParams(
            collectionId: $collectionId = $collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
            operator: $operator = app(Generator::class)->public_key(),
            amount: $amount = $tokenAccount->balance,
            currentAmount: $currentAmount = $tokenAccount->balance
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_chain_id),
            'amount' => $amount,
            'currentAmount' => $currentAmount,
            'operator' => SS58Address::encode($operator),
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

    public function test_it_can_approve_a_token_with_big_int_current_amount(): void
    {
        $collection = Collection::factory(['owner_wallet_id' => $this->wallet->id])->create();
        $token = Token::factory(['collection_id' => $collection->id])->create();
        $tokenAccount = TokenAccount::factory([
            'collection_id' => $collection->id,
            'token_id' => $token->id,
            'wallet_id' => $this->wallet,
            'balance' => Hex::MAX_UINT128,
        ])->create();

        $collection = Collection::find($tokenAccount->collection_id);
        $token = Token::find($tokenAccount->token_id);

        $encodedData = TransactionSerializer::encode($this->method, ApproveTokenMutation::getEncodableParams(
            collectionId: $collectionId = $collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
            operator: $operator = app(Generator::class)->public_key(),
            amount: $amount = fake()->numberBetween(),
            currentAmount: $currentAmount = $tokenAccount->balance
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_chain_id),
            'amount' => $amount,
            'currentAmount' => $currentAmount,
            'operator' => SS58Address::encode($operator),
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

    public function test_it_will_fail_with_no_args(): void
    {
        $response = $this->graphql($this->method, [], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of required type "BigInt!" was not provided.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'amount' => $this->tokenAccount->balance,
            'currentAmount' => $this->tokenAccount->balance,
            'operator' => app(Generator::class)->public_key(),
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of required type "BigInt!" was not provided.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => null,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'amount' => $this->tokenAccount->balance,
            'currentAmount' => $this->tokenAccount->balance,
            'operator' => app(Generator::class)->public_key(),
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of non-null type "BigInt!" must not be null.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => 'invalid',
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'amount' => $this->tokenAccount->balance,
            'currentAmount' => $this->tokenAccount->balance,
            'operator' => app(Generator::class)->public_key(),
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value "invalid"; Cannot represent following value as uint256',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_collection_id_non_existent(): void
    {
        Collection::where('collection_chain_id', '=', $collectionId = fake()->numberBetween(2000))?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'amount' => $this->tokenAccount->balance,
            'currentAmount' => $this->tokenAccount->balance,
            'operator' => app(Generator::class)->public_key(),
        ], true);

        $this->assertArrayContainsArray(
            ['collectionId' => ['The selected collection id is invalid.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'amount' => $this->tokenAccount->balance,
            'currentAmount' => $this->tokenAccount->balance,
            'operator' => app(Generator::class)->public_key(),
        ], true);

        $this->assertStringContainsString(
            'Variable "$tokenId" of required type "EncodableTokenIdInput!" was not provided.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => ['integer' => null],
            'amount' => $this->tokenAccount->balance,
            'currentAmount' => $this->tokenAccount->balance,
            'operator' => app(Generator::class)->public_key(),
        ], true);

        $this->assertStringContainsString(
            'The integer field must have a value.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_simulate_invalid(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'amount' => $this->tokenAccount->balance,
            'currentAmount' => $this->tokenAccount->balance,
            'operator' => app(Generator::class)->public_key(),
            'simulate' => 'invalid',
        ], true);

        $this->assertStringContainsString(
            'Variable "$simulate" got invalid value "invalid"',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => ['integer' => 'invalid'],
            'amount' => $this->tokenAccount->balance,
            'currentAmount' => $this->tokenAccount->balance,
            'operator' => app(Generator::class)->public_key(),
        ], true);

        $this->assertStringContainsString(
            'Variable "$tokenId" got invalid value "invalid" at "tokenId.integer"; Cannot represent following value as uint256',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_token_id_non_existent(): void
    {
        Token::where('token_chain_id', '=', $tokenId = fake()->numberBetween())?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => ['integer' => $tokenId],
            'amount' => $this->tokenAccount->balance,
            'currentAmount' => $this->tokenAccount->balance,
            'operator' => app(Generator::class)->public_key(),
        ], true);

        $this->assertArrayContainsArray(
            ['tokenId' => ['The token id doesn\'t exist.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_amount(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'currentAmount' => $this->tokenAccount->balance,
            'operator' => app(Generator::class)->public_key(),
        ], true);

        $this->assertStringContainsString(
            'Variable "$amount" of required type "BigInt!" was not provided.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_amount(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'amount' => null,
            'currentAmount' => $this->tokenAccount->balance,
            'operator' => app(Generator::class)->public_key(),
        ], true);

        $this->assertStringContainsString(
            'Variable "$amount" of non-null type "BigInt!" must not be null.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_negative_amount(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'amount' => -1,
            'currentAmount' => $this->tokenAccount->balance,
            'operator' => app(Generator::class)->public_key(),
        ], true);

        $this->assertStringContainsString(
            'Variable "$amount" got invalid value -1; Cannot represent following value as uint256',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_zero_amount(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'amount' => 0,
            'currentAmount' => $this->tokenAccount->balance,
            'operator' => app(Generator::class)->public_key(),
        ], true);

        $this->assertArrayContainsArray(
            ['amount' => ['The amount is too small, the minimum value it can be is 1.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_amount(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'amount' => 'invalid',
            'currentAmount' => $this->tokenAccount->balance,
            'operator' => app(Generator::class)->public_key(),
        ], true);

        $this->assertStringContainsString(
            'Variable "$amount" got invalid value "invalid"; Cannot represent following value as uint256',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_current_amount(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'amount' => $this->tokenAccount->balance,
            'operator' => app(Generator::class)->public_key(),
        ], true);

        $this->assertStringContainsString(
            'Variable "$currentAmount" of required type "BigInt!" was not provided.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_current_amount(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'amount' => $this->tokenAccount->balance,
            'currentAmount' => null,
            'operator' => app(Generator::class)->public_key(),
        ], true);

        $this->assertStringContainsString(
            'Variable "$currentAmount" of non-null type "BigInt!" must not be null.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_negative_current_amount(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'amount' => $this->tokenAccount->balance,
            'currentAmount' => -1,
            'operator' => app(Generator::class)->public_key(),
        ], true);

        $this->assertStringContainsString(
            'Variable "$currentAmount" got invalid value -1; Cannot represent following value as uint256',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_current_amount(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'amount' => $this->tokenAccount->balance,
            'currentAmount' => 'invalid',
            'operator' => app(Generator::class)->public_key(),
        ], true);

        $this->assertStringContainsString(
            'Variable "$currentAmount" got invalid value "invalid"; Cannot represent following value as uint256',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_negative_expiration(): void
    {
        Block::truncate();
        $block = Block::factory()->create();
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'amount' => $this->tokenAccount->balance,
            'currentAmount' => $this->tokenAccount->balance,
            'operator' => app(Generator::class)->public_key(),
            'expiration' => -1,
        ], true);

        $this->assertArrayContainsArray(
            ['expiration' => ["The expiration must be at least {$block->number}."]],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_expiration(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'amount' => $this->tokenAccount->balance,
            'currentAmount' => $this->tokenAccount->balance,
            'operator' => app(Generator::class)->public_key(),
            'expiration' => 'invalid',
        ], true);

        $this->assertStringContainsString(
            'Variable "$expiration" got invalid value "invalid"; Int cannot represent non-integer value: "invalid"',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_when_passing_daemon_as_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'amount' => $this->tokenAccount->balance,
            'currentAmount' => $this->tokenAccount->balance,
            'operator' => Account::daemonPublicKey(),
        ], true);

        $this->assertArrayContainsArray(
            ['operator' => ['The operator cannot be set to the daemon account.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
