<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Enums\Substrate\FreezeStateType;
use Enjin\Platform\Enums\Substrate\FreezeType;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\FreezeMutation;
use Enjin\Platform\Models\Indexer\Account;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Indexer\CollectionAccount;
use Enjin\Platform\Models\Indexer\Token;
use Enjin\Platform\Models\Indexer\TokenAccount;
use Enjin\Platform\Models\Substrate\FreezeTypeParams;
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

class FreezeTest extends TestCaseGraphQL
{
    use MocksHttpClient;

    protected string $method = 'Freeze';
    protected Codec $codec;
    protected Account $wallet;
    protected Collection $collection;
    protected CollectionAccount $collectionAccount;
    protected Token $token;
    protected Encoder $tokenIdEncoder;
    protected TokenAccount $tokenAccount;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->codec = new Codec();
        $this->wallet = Address::daemon();
        $this->collection = Collection::factory()->create(['owner_id' => $this->wallet->id]);
        $this->token = Token::factory(['collection_id' => $this->collection->id])->create();
        $this->tokenAccount = TokenAccount::factory([
            'account_id' => $this->wallet,
            'collection_id' => $this->collection,
            'token_id' => $this->token,
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
        $encodedData = TransactionSerializer::encode($this->method, FreezeMutation::getEncodableParams(
            collectionId: $collectionId = random_int(1, 1000),
            freezeParams: new FreezeTypeParams(
                type: $freezeType = FreezeType::COLLECTION
            ),
        ));

        $response = $this->graphql($this->method, [
            'freezeType' => $freezeType->name,
            'collectionId' => $collectionId,
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
        $encodedData = TransactionSerializer::encode($this->method, FreezeMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->id,
            freezeParams: new FreezeTypeParams(
                type: $freezeType = FreezeType::COLLECTION
            ),
        ));

        $this->mockFee($feeDetails = app(Generator::class)->fee_details());
        $response = $this->graphql($this->method, [
            'freezeType' => $freezeType->name,
            'collectionId' => $collectionId,
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
        $collection = Collection::factory()->create(['owner_id' => Account::factory()->create()]);
        $response = $this->graphql($this->method, $params = [
            'freezeType' => FreezeType::COLLECTION->name,
            'collectionId' => $collection->id,
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

    public function test_can_freeze_a_collection(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, FreezeMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->id,
            freezeParams: new FreezeTypeParams(
                type: $freezeType = FreezeType::COLLECTION
            ),
        ));

        $response = $this->graphql($this->method, [
            'freezeType' => $freezeType->name,
            'collectionId' => $collectionId,
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

    public function test_can_freeze_a_collection_with_ss58_signing_account(): void
    {
        $signingWallet = Account::factory([
            'id' => $signingAccount = app(Generator::class)->public_key(),
        ])->create();
        $collection = Collection::factory(['owner_id' => $signingWallet])->create();

        $encodedData = TransactionSerializer::encode($this->method, FreezeMutation::getEncodableParams(
            collectionId: $collectionId = $collection->id,
            freezeParams: new FreezeTypeParams(
                type: $freezeType = FreezeType::COLLECTION
            ),
        ));

        $response = $this->graphql($this->method, [
            'freezeType' => $freezeType->name,
            'collectionId' => $collectionId,
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

    public function test_can_freeze_a_collection_with_public_key_signing_account(): void
    {
        $wallet = Account::factory([
            'id' => $signingAccount = app(Generator::class)->public_key,
        ])->create();
        $collection = Collection::factory([
            'owner_id' => $wallet,
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, FreezeMutation::getEncodableParams(
            collectionId: $collectionId = $collection->id,
            freezeParams: new FreezeTypeParams(
                type: $freezeType = FreezeType::COLLECTION
            ),
        ));

        $response = $this->graphql($this->method, [
            'freezeType' => $freezeType->name,
            'collectionId' => $collectionId,
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

    public function test_can_freeze_a_big_int_collection(): void
    {
        $this->deleteAllFrom($collectionId = Hex::MAX_UINT128);

        $collection = Collection::factory([
            'id' => $collectionId = Hex::MAX_UINT128,
            'owner_id' => $this->wallet,
        ])->create();
        CollectionAccount::factory([
            'collection_id' => $collection,
            'account_id' => $this->wallet,
            'account_count' => 1,
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, FreezeMutation::getEncodableParams(
            collectionId: $collectionId,
            freezeParams: new FreezeTypeParams(
                type: $freezeType = FreezeType::COLLECTION
            ),
        ));

        $response = $this->graphql($this->method, [
            'freezeType' => $freezeType->name,
            'collectionId' => $collectionId,
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

    public function test_can_freeze_a_collection_account(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, FreezeMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->id,
            freezeParams: new FreezeTypeParams(
                type: $freezeType = FreezeType::COLLECTION_ACCOUNT,
                account: $account = $this->wallet->id,
            ),
        ));

        $response = $this->graphql($this->method, [
            'freezeType' => $freezeType->name,
            'collectionId' => $collectionId,
            'collectionAccount' => SS58Address::encode($account),
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

    public function test_can_freeze_a_token_using_adapter(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, FreezeMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->id,
            freezeParams: new FreezeTypeParams(
                type: $freezeType = FreezeType::TOKEN,
                token: $this->tokenIdEncoder->encode(),
            ),
        ));

        $response = $this->graphql($this->method, [
            'freezeType' => $freezeType->name,
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
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

    public function test_can_freeze_a_token_without_freeze_state(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, FreezeMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->id,
            freezeParams: new FreezeTypeParams(
                type: $freezeType = FreezeType::TOKEN,
                token: $this->tokenIdEncoder->encode(),
            ),
        ));

        $response = $this->graphql($this->method, [
            'freezeType' => $freezeType->name,
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
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

    public function test_can_freeze_a_token_with_freeze_state(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, FreezeMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->id,
            freezeParams: new FreezeTypeParams(
                type: $freezeType = FreezeType::TOKEN,
                token: $this->tokenIdEncoder->encode(),
                freezeState: $freezeState = FreezeStateType::TEMPORARY,
            ),
        ));

        $response = $this->graphql($this->method, [
            'freezeType' => $freezeType->name,
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'freezeState' => $freezeState->name,
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

    public function test_can_freeze_a_big_int_token(): void
    {
        $collection = Collection::factory()->create(['owner_id' => $this->wallet]);
        Token::factory([
            'collection_id' => $collection,
            'token_id' => $tokenId = Hex::MAX_UINT128,
        ])->create();

        CollectionAccount::factory([
            'collection_id' => $collection,
            'account_id' => $this->wallet,
            'account_count' => 1,
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, FreezeMutation::getEncodableParams(
            collectionId: $collectionId = $collection->id,
            freezeParams: new FreezeTypeParams(
                type: $freezeType = FreezeType::TOKEN,
                token: $this->tokenIdEncoder->encode($tokenId),
            ),
        ));

        $response = $this->graphql($this->method, [
            'freezeType' => $freezeType->name,
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
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

    public function test_can_freeze_a_token_account(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, FreezeMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->id,
            freezeParams: new FreezeTypeParams(
                type: $freezeType = FreezeType::TOKEN_ACCOUNT,
                token: $this->tokenIdEncoder->encode(),
                account: $account = $this->wallet->id,
            ),
        ));

        $response = $this->graphql($this->method, [
            'freezeType' => $freezeType->name,
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'tokenAccount' => SS58Address::encode($account),
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

    public function test_it_will_fail_with_freeze_type_non_existent(): void
    {
        $response = $this->graphql($this->method, [
            'freezeType' => 'ASSET',
            'collectionId' => $this->collection->id,
        ], true);

        $this->assertStringContainsString(
            'got invalid value "ASSET"; Value "ASSET" does not exist in "FreezeType" enum',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_collection_id_non_existent(): void
    {
        Collection::where('id', '=', $collectionId = fake()->numberBetween(2000))?->delete();

        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::COLLECTION->name,
            'collectionId' => $collectionId,
        ], true);

        $this->assertArrayContainsArray(
            ['collectionId' => ['The selected collection id is invalid.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::COLLECTION->name,
            'collectionId' => 'invalid',
        ], true);

        $this->assertStringContainsString(
            'got invalid value "invalid"; Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::COLLECTION->name,
            'collectionId' => null,
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of non-null type "BigInt!" must not be null.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::COLLECTION->name,
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of required type "BigInt!" was not provided.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::TOKEN->name,
            'collectionId' => $this->collection->id,
            'tokenId' => null,
        ], true);

        $this->assertArrayContainsArray(
            ['tokenId' => ['The token id field is required.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::TOKEN->name,
            'collectionId' => $this->collection->id,
        ], true);

        $this->assertArrayContainsArray(
            ['tokenId' => ['The token id field is required.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::TOKEN->name,
            'collectionId' => $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable('invalid'),
        ], true);

        $this->assertStringContainsString(
            'value "invalid" at "tokenId.integer"; Cannot represent following value as uint256: "invalid"',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_freeze_state(): void
    {
        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::TOKEN->name,
            'collectionId' => $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable('invalid'),
            'freezeState' => 'invalid',
        ], true);

        $this->assertStringContainsString(
            'value "invalid" at "tokenId.integer"; Cannot represent following value as uint256: "invalid"',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_freeze_state_in_other_type(): void
    {
        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::COLLECTION->name,
            'collectionId' => $this->collection->id,
            'freezeState' => FreezeStateType::TEMPORARY->name,
        ], true);

        $this->assertArrayContainsArray(
            ['freezeState' => ['The freeze state field is prohibited.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_token_id_non_existent(): void
    {
        Token::where('token_id', '=', $tokenId = fake()->numberBetween())?->delete();

        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::TOKEN->name,
            'collectionId' => $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
        ], true);

        $this->assertArrayContainsArray(
            ['tokenId' => ['The token id does not exist in the specified collection.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_collection_account(): void
    {
        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::COLLECTION_ACCOUNT->name,
            'collectionId' => $this->collection->id,
            'collectionAccount' => null,
        ], true);

        $this->assertArrayContainsArray(
            ['collectionAccount' => ['The collection account field is required.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_collection_account(): void
    {
        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::COLLECTION_ACCOUNT->name,
            'collectionId' => $this->collection->id,
        ], true);

        $this->assertArrayContainsArray(
            ['collectionAccount' => ['The collection account field is required.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_collection_account(): void
    {
        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::COLLECTION_ACCOUNT->name,
            'collectionId' => $this->collection->id,
            'collectionAccount' => 'invalid',
        ], true);

        $this->assertArrayContainsArray(
            ['collectionAccount' => ['The collection account is not a valid substrate account.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_collection_account_non_existent(): void
    {
        Account::where('public_key', '=', $address = app(Generator::class)->public_key())?->delete();

        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::COLLECTION_ACCOUNT->name,
            'collectionId' => $collectionId = $this->collection->id,
            'collectionAccount' => $address,
        ], true);

        $this->assertArrayContainsArray(
            ['collectionAccount' => ["Could not find a collection account for {$address} at collection {$collectionId}."]],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_token_account_non_existent(): void
    {
        Account::where('public_key', '=', $publicKey = app(Generator::class)->public_key())?->delete();

        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::TOKEN_ACCOUNT->name,
            'collectionId' => $collectionId = $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId = $this->token->token_id),
            'tokenAccount' => $tokenAccount = SS58Address::encode($publicKey),
        ], true);

        $this->assertArrayContainsArray(
            ['tokenAccount' => ["Could not find a token account for {$tokenAccount} at collection {$collectionId} and token {$tokenId}."]],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_token_account(): void
    {
        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::TOKEN_ACCOUNT->name,
            'collectionId' => $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'tokenAccount' => null,
        ], true);

        $this->assertArrayContainsArray(
            ['tokenAccount' => ['The token account field is required.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_token_account(): void
    {
        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::TOKEN_ACCOUNT->name,
            'collectionId' => $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
        ], true);

        $this->assertArrayContainsArray(
            ['tokenAccount' => ['The token account field is required.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_token_account(): void
    {
        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::TOKEN_ACCOUNT->name,
            'collectionId' => $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'tokenAccount' => 'invalid',
        ], true);

        $this->assertArrayContainsArray(
            ['tokenAccount' => ['The token account is not a valid substrate account.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_cant_pass_token_id_when_freezing_collection(): void
    {
        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::COLLECTION->name,
            'collectionId' => $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
        ], true);

        $this->assertArrayContainsArray(
            ['tokenId' => ['The token id field is prohibited.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_cant_pass_collection_account_when_freezing_collection(): void
    {
        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::COLLECTION->name,
            'collectionId' => $this->collection->id,
            'collectionAccount' => $this->wallet->id,
        ], true);

        $this->assertArrayContainsArray(
            ['collectionAccount' => ['The collection account field is prohibited.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_cant_pass_token_account_when_freezing_collection(): void
    {
        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::COLLECTION->name,
            'collectionId' => $this->collection->id,
            'tokenAccount' => $this->wallet->id,
        ], true);

        $this->assertArrayContainsArray(
            ['tokenAccount' => ['The token account field is prohibited.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_cant_pass_token_account_when_freezing_collection_account(): void
    {
        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::COLLECTION_ACCOUNT->name,
            'collectionId' => $this->collection->id,
            'collectionAccount' => $this->wallet->id,
            'tokenAccount' => $this->wallet->id,
        ], true);

        $this->assertArrayContainsArray(
            ['tokenAccount' => ['The token account field is prohibited.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_cant_pass_token_id_when_freezing_collection_account(): void
    {
        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::COLLECTION_ACCOUNT->name,
            'collectionId' => $this->collection->id,
            'collectionAccount' => $this->wallet->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable($this->token->token_id),
        ], true);

        $this->assertArrayContainsArray(
            ['tokenId' => ['The token id field is prohibited.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_cant_pass_token_account_when_freezing_token(): void
    {
        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::TOKEN->name,
            'collectionId' => $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'tokenAccount' => $this->wallet->id,
        ], true);

        $this->assertArrayContainsArray(
            ['tokenAccount' => ['The token account field is prohibited.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_cant_pass_collection_account_when_freezing_token(): void
    {
        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::TOKEN->name,
            'collectionId' => $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'collectionAccount' => $this->wallet->id,
        ], true);

        $this->assertArrayContainsArray(
            ['collectionAccount' => ['The collection account field is prohibited.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_cant_pass_collection_account_when_freezing_token_account(): void
    {
        $response = $this->graphql($this->method, [
            'freezeType' => FreezeType::TOKEN_ACCOUNT->name,
            'collectionId' => $this->collection->id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'collectionAccount' => SS58Address::encode($this->wallet->id),
            'tokenAccount' => SS58Address::encode($this->wallet->id),
        ], true);

        $this->assertArrayContainsArray(
            ['collectionAccount' => ['The collection account field is prohibited.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
