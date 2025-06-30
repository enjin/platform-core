<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\UnapproveTokenMutation;
use Enjin\Platform\Models\Indexer\Account;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Indexer\CollectionAccount;
use Enjin\Platform\Models\Indexer\Token;
use Enjin\Platform\Models\Indexer\TokenAccount;
use Enjin\Platform\Models\TokenAccountApproval;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Override;

class UnapproveTokenTest extends TestCaseGraphQL
{
    use MocksHttpClient;

    protected string $method = 'UnapproveToken';
    protected Codec $codec;
    protected Account $wallet;
    protected Account $operator;
    protected Collection $collection;
    protected Token $token;
    protected Encoder $tokenIdEncoder;
    protected TokenAccount $tokenAccount;
    protected Model $tokenAccountApproval;
    protected CollectionAccount $collectionAccount;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->codec = new Codec();
        $this->wallet = Account::daemon();
        $this->collection = Collection::factory(['owner_id' => $this->wallet])->create();
        $this->collectionAccount = CollectionAccount::factory([
            'collection_id' => $this->collection,
            'wallet_id' => $this->wallet,
            'account_count' => 1,
        ])->create();
        $this->token = Token::factory([
            'collection_id' => $this->collection,
        ])->create();
        $this->tokenAccount = TokenAccount::factory([
            'wallet_id' => $this->wallet,
            'collection_id' => $this->collection,
            'token_id' => $this->token,
        ])->create();
        $this->tokenAccountApproval = TokenAccountApproval::factory([
            'token_account_id' => $this->tokenAccount,
        ])->create();
        $this->operator = Account::find($this->tokenAccountApproval->wallet_id);
        $this->tokenIdEncoder = new Integer($this->token->token_chain_id);
    }

    // Happy Path

    public function test_it_can_skip_validation(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, UnapproveTokenMutation::getEncodableParams(
            collectionId: $collectionId = random_int(1, 1000),
            tokenId: $this->tokenIdEncoder->encode(),
            operator: $operator = $this->operator->public_key,
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'operator' => SS58Address::encode($operator),
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
        $encodedData = TransactionSerializer::encode($this->method, UnapproveTokenMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode(),
            operator: $operator = $this->operator->public_key,
        ));

        $this->mockFee($feeDetails = app(Generator::class)->fee_details());
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
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

    public function test_it_can_bypass_ownership(): void
    {
        $signingWallet = Account::factory()->create();
        $collection = Collection::factory()->create(['owner_id' => $signingWallet]);
        $token = Token::factory([
            'collection_id' => $collection,
        ])->create();
        $tokenAccount = TokenAccount::factory([
            'collection_id' => $collection,
            'token_id' => $token,
            'wallet_id' => $this->wallet,
        ])->create();
        TokenAccountApproval::factory()->create([
            'token_account_id' => $tokenAccount->id,
            'wallet_id' => $operator = Account::factory()->create(),
        ]);
        $response = $this->graphql($this->method, $params = [
            'collectionId' => $collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_chain_id),
            'operator' => SS58Address::encode($operator->public_key),
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

    public function test_it_can_unapprove_a_token(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, UnapproveTokenMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode(),
            operator: $operator = $this->operator->public_key,
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
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

    public function test_it_can_unapprove_a_token_with_signing_account_ss58(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, UnapproveTokenMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode(),
            operator: $operator = $this->operator->public_key,
        ));

        $newOwner = Account::factory()->create([
            'public_key' => $signingAccount = app(Generator::class)->public_key(),
        ]);
        $this->collection->update(['owner_id' => $newOwner->id]);
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'operator' => SS58Address::encode($operator),
            'signingAccount' => SS58Address::encode($signingAccount),
        ]);
        $this->collection->update(['owner_id' => $this->wallet->id]);

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

    public function test_it_can_unapprove_a_token_with_signing_account_public_key(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, UnapproveTokenMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode(),
            operator: $operator = $this->operator->public_key,
        ));

        $newOwner = Account::factory()->create([
            'public_key' => $signingAccount = app(Generator::class)->public_key(),
        ]);
        $this->collection->update(['owner_id' => $newOwner->id]);
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'operator' => SS58Address::encode($operator),
            'signingAccount' => $signingAccount,
        ]);
        $this->collection->update(['owner_id' => $this->wallet->id]);

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

    public function test_it_can_unapprove_a_token_with_big_int_collection_id(): void
    {
        Collection::where('collection_chain_id', Hex::MAX_UINT128)->update(['collection_chain_id' => fake()->numberBetween()]);
        $collection = Collection::factory([
            'collection_chain_id' => Hex::MAX_UINT128,
            'owner_id' => $this->wallet,
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
            'wallet_id' => $this->wallet,
            'collection_id' => $collection,
            'token_id' => $token,
        ])->create();
        $operator = Account::factory()->create();
        TokenAccountApproval::factory([
            'token_account_id' => $tokenAccount,
            'wallet_id' => $operator,
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, UnapproveTokenMutation::getEncodableParams(
            collectionId: $collectionId = $collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
            operator: $operator = $operator->public_key,
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_chain_id),
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

    public function test_it_can_unapprove_a_token_with_big_int_token_id(): void
    {
        $collection = Collection::factory(['owner_id' => $this->wallet])->create();
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
            'wallet_id' => $this->wallet,
            'collection_id' => $collection,
            'token_id' => $token,
        ])->create();

        $operator = Account::factory()->create();
        TokenAccountApproval::factory([
            'token_account_id' => $tokenAccount,
            'wallet_id' => $operator,
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, UnapproveTokenMutation::getEncodableParams(
            collectionId: $collectionId = $collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
            operator: $operator = $operator->public_key,
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_chain_id),
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
            'operator' => $this->operator->public_key,
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
            'operator' => $this->operator->public_key,
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
            'operator' => $this->operator->public_key,
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
            'operator' => SS58Address::encode($this->operator->public_key),
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
            'operator' => $this->operator->public_key,
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
            'tokenId' => null,
            'operator' => $this->operator->public_key,
        ], true);

        $this->assertStringContainsString(
            'Variable "$tokenId" of non-null type "EncodableTokenIdInput!" must not be null.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => 'invalid',
            'operator' => $this->operator->public_key,
        ], true);

        $this->assertStringContainsString(
            'Variable "$tokenId" got invalid value "invalid"; Expected type "EncodableTokenIdInput" to be an object',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_token_id_non_existent(): void
    {
        Token::where('token_chain_id', '=', $tokenId = fake()->numberBetween())?->delete();
        $operator = SS58Address::encode($this->operator->public_key);

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => ['integer' => $tokenId],
            'operator' => $operator,
        ], true);

        $this->assertArrayContainsArray(
            ['operator' => ["Could not find an approval for {$operator} at collection {$this->collection->collection_chain_id} and token {$tokenId}."]],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
        ], true);

        $this->assertStringContainsString(
            'Variable "$operator" of required type "String!" was not provided.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'operator' => null,
        ], true);

        $this->assertStringContainsString(
            'Variable "$operator" of non-null type "String!" must not be null.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'operator' => 'invalid',
        ], true);

        $this->assertArrayContainsArray(
            ['operator' => ['The operator is not a valid substrate account.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_not_found_approval(): void
    {
        Account::where('public_key', '=', $operator = app(Generator::class)->public_key())?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'operator' => $operator = SS58Address::encode($operator),
        ], true);

        $this->assertArrayContainsArray(
            ['operator' => ["Could not find an approval for {$operator} at collection {$collectionId} and token {$this->token->token_chain_id}."]],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
