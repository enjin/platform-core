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
    protected CollectionAccount $collectionAccount;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->codec = new Codec();
        $this->wallet = $this->getDaemonAccount();

        $this->collection = Collection::factory(['owner_id' => $accountId = $this->wallet->id])->create();

        $this->collectionAccount = CollectionAccount::factory([
            'collection_id' => $collectionId = $this->collection,
            'account_id' => $accountId,
            'account_count' => 1,
            'id' => "{$accountId}-{$collectionId}",
        ])->create();

        $this->token = Token::factory([
            'collection_id' => $collectionId,
            'token_id' => $tokenId = fake()->numberBetween(),
            'id' => "{$collectionId}-{$tokenId}",
        ])->create();

        $this->tokenAccount = TokenAccount::factory([
            'account_id' => $accountId,
            'collection_id' => $collectionId,
            'token_id' => $this->token,
            'id' => "{$accountId}-{$collectionId}-{$tokenId}",
            'approvals' => [['accountId' => ($this->operator = Account::factory()->create())->id]],
        ])->create();

        $this->tokenIdEncoder = new Integer($tokenId);
    }

    // Happy Path

    public function test_it_can_skip_validation(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, UnapproveTokenMutation::getEncodableParams(
            collectionId: $collectionId = fake()->numberBetween(),
            tokenId: $this->tokenIdEncoder->encode(),
            operator: $operator = $this->operator->id,
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
            collectionId: $collectionId = $this->collection->id,
            tokenId: $this->tokenIdEncoder->encode(),
            operator: $operatorId = $this->operator->id,
        ));

        $this->mockFee($feeDetails = app(Generator::class)->fee_details());

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'operator' => SS58Address::encode($operatorId),
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
        $owner = Account::factory()->create();

        $collection = Collection::factory([
            'owner_id' => $ownerId = $owner->id,
        ])->create();

        $token = Token::factory([
            'collection_id' => $collectionId = $collection->id,
            'token_id' => $tokenId = fake()->numberBetween(),
            'id' => "{$collectionId}-{$tokenId}",
        ])->create();

        TokenAccount::factory([
            'collection_id' => $collectionId,
            'token_id' => $token,
            'account_id' => $ownerId,
            'id' => "{$ownerId}-{$collectionId}-{$tokenId}",
            'approvals' => [['accountId' => $operatorId = (Account::factory()->create()->id)]],
        ])->create();

        $response = $this->graphql($this->method, $params = [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
            'operator' => SS58Address::encode($operatorId),
            'nonce' => fake()->numberBetween(),
        ], true);

        $this->assertArrayContainsArray(
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
        $owner = Account::factory()->create();

        $collection = Collection::factory([
            'owner_id' => $ownerId = $owner->id,
        ])->create();

        $token = Token::factory([
            'collection_id' => $collectionId = $collection->id,
            'token_id' => $tokenId = fake()->numberBetween(),
            'id' => "{$collectionId}-{$tokenId}",
        ])->create();

        TokenAccount::factory([
            'collection_id' => $collectionId,
            'token_id' => $token,
            'account_id' => $ownerId,
            'id' => "{$ownerId}-{$collectionId}-{$tokenId}",
            'approvals' => [['accountId' => $operatorId = (Account::factory()->create()->id)]],
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, UnapproveTokenMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode($tokenId),
            operator: $operatorId,
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
            'operator' => SS58Address::encode($operatorId),
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
        $collection = Collection::factory([
            'owner_id' => $ownerId = Account::factory()->create()->id,
        ])->create();

        $token = Token::factory([
            'collection_id' => $collectionId = $collection->id,
            'token_id' => $tokenId = fake()->numberBetween(),
            'id' => "{$collectionId}-{$tokenId}",
        ])->create();

        TokenAccount::factory([
            'collection_id' => $collectionId,
            'token_id' => $token,
            'account_id' => $ownerId,
            'id' => "{$ownerId}-{$collectionId}-{$tokenId}",
            'approvals' => [['accountId' => $this->operator->id]],
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, UnapproveTokenMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode($tokenId),
            operator: $operator = $this->operator->id,
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
            'operator' => SS58Address::encode($operator),
            'signingAccount' => SS58Address::encode($ownerId),
        ]);

        $this->assertArrayContainsArray([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => [
                'account' => [
                    'publicKey' => $ownerId,
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
            collectionId: $collectionId = $this->collection->id,
            tokenId: $this->tokenIdEncoder->encode(),
            operator: $operator = $this->operator->id,
        ));

        $newOwner = Account::factory()->create([
            'id' => $signingAccount = app(Generator::class)->public_key(),
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
        $this->deleteAllFrom($collectionId = Hex::MAX_UINT128);

        $operator = Account::factory()->create();

        Collection::factory([
            'owner_id' => $ownerId = $this->wallet->id,
            'id' => $collectionId,
        ])->create();

        CollectionAccount::factory([
            'collection_id' => $collectionId,
            'account_id' => $ownerId,
            'account_count' => 1,
            'id' => "{$ownerId}-{$collectionId}",
        ]);

        $token = Token::factory([
            'collection_id' => $collectionId,
            'token_id' => $tokenId = fake()->numberBetween(),
            'id' => "{$collectionId}-{$tokenId}",
        ])->create();

        $ta = TokenAccount::factory([
            'account_id' => $ownerId,
            'collection_id' => $collectionId,
            'token_id' => $token,
            'id' => "{$ownerId}-{$collectionId}-{$tokenId}",
            'approvals' => [['accountId' => $operator->id]],
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, UnapproveTokenMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode($tokenId),
            operator: $operator = $operator->id,
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
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
        $this->deleteAllFrom($collectionId = fake()->numberBetween());

        $operator = Account::factory()->create();

        $collection = Collection::factory([
            'owner_id' => $ownerId = $this->wallet,
            'id' => $collectionId,
        ])->create();

        CollectionAccount::factory([
            'collection_id' => $collectionId = $collection->id,
            'account_id' => $ownerId,
            'account_count' => 1,
            'id' => "{$ownerId}-{$collectionId}",
        ])->create();

        $token = Token::factory([
            'collection_id' => $collectionId,
            'token_id' => $tokenId = Hex::MAX_UINT128,
            'id' => "{$collectionId}-{$tokenId}",
        ])->create();

        TokenAccount::factory([
            'account_id' => $ownerId,
            'collection_id' => $collectionId,
            'token_id' => $token,
            'id' => "{$ownerId}-{$collectionId}-{$tokenId}",
            'approvals' => [['accountId' => $operator->id]],
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, UnapproveTokenMutation::getEncodableParams(
            collectionId: $collectionId,
            tokenId: $this->tokenIdEncoder->encode($tokenId),
            operator: $operatorId = $operator->id,
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
            'operator' => SS58Address::encode($operatorId),
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
            'operator' => $this->operator->id,
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
            'operator' => $this->operator->id,
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
            'operator' => $this->operator->id,
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value "invalid"; Cannot represent following value as uint256',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_collection_id_non_existent(): void
    {
        $this->deleteAllFrom($collectionId = fake()->numberBetween());

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'operator' => SS58Address::encode($this->operator->id),
        ], true);

        $this->assertArrayContainsArray([
            'collectionId' => ['The selected collection id is invalid.'],
        ], $response['error']);

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'operator' => $this->operator->id,
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
            'collectionId' => $this->collection->id,
            'tokenId' => null,
            'operator' => $this->operator->id,
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
            'collectionId' => $this->collection->id,
            'tokenId' => 'invalid',
            'operator' => $this->operator->id,
        ], true);

        $this->assertStringContainsString(
            'Variable "$tokenId" got invalid value "invalid"; Expected type "EncodableTokenIdInput" to be an object',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_token_id_non_existent(): void
    {
        $this->deleteAllFrom($this->collection->id, $tokenId = fake()->numberBetween());
        $operator = SS58Address::encode($this->operator->id);

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
            'tokenId' => ['integer' => $tokenId],
            'operator' => $operator,
        ], true);

        $this->assertArrayContainsArray(
            ['operator' => ["Could not find an approval for {$operator} at collection {$this->collection->id} and token {$tokenId}."]],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_operator(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->id,
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
            'collectionId' => $this->collection->id,
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
            'collectionId' => $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'operator' => 'invalid',
        ], true);

        $this->assertArrayContainsArray([
            'operator' => ['The operator is not a valid substrate account.'],
        ], $response['error']);

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_not_found_approval(): void
    {
        Account::find($operator = app(Generator::class)->public_key())?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'operator' => $operator = SS58Address::encode($operator),
        ], true);

        $this->assertArrayContainsArray(
            ['operator' => ["Could not find an approval for {$operator} at collection {$collectionId} and token {$this->token->token_id}."]],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
