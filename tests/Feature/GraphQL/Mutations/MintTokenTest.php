<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Substrate\MintParams;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Token\Encoder;
use Enjin\Platform\Services\Token\Encoders\Integer;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

class MintTokenTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;

    protected string $method = 'MintToken';
    protected Codec $codec;
    protected string $defaultAccount;
    protected Model $collection;
    protected Model $token;
    protected Encoder $tokenIdEncoder;
    protected Model $recipient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->codec = new Codec();
        $this->token = Token::factory()->create();
        $this->tokenIdEncoder = new Integer($this->token->token_chain_id);
        $this->collection = Collection::find($this->token->collection_id);
        $this->recipient = Wallet::factory()->create();
        $this->defaultAccount = config('enjin-platform.chains.daemon-account');
    }

    // Happy Path

    public function test_can_mint_a_token_without_unit_price(): void
    {
        $encodedData = $this->codec->encode()->mint(
            $recipient = $this->recipient->public_key,
            $collectionId = $this->collection->collection_chain_id,
            $params = new MintParams(
                tokenId: $this->tokenIdEncoder->encode(),
                amount: fake()->numberBetween(),
            ),
        );

        $params = $params->toArray()['Mint'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($recipient),
            'collectionId' => $collectionId,
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

    public function test_can_mint_a_token_with_different_types(): void
    {
        $encodedData = $this->codec->encode()->mint(
            $recipient = $this->recipient->public_key,
            $collectionId = $this->collection->collection_chain_id,
            new MintParams(
                tokenId: $this->tokenIdEncoder->encode(),
                amount: $amount = fake()->numberBetween(),
            ),
        );

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($recipient),
            'collectionId' => (int) $collectionId,
            'params' => [
                'tokenId' => $this->tokenIdEncoder->toEncodable(),
                'amount' => (string) $amount,
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

    public function test_can_mint_a_token_with_bigint_collection_id_and_token_id(): void
    {
        $collection = Collection::factory([
            'collection_chain_id' => Hex::MAX_UINT128,
        ])->create();

        $token = Token::factory([
            'collection_id' => $collection->id,
            'token_chain_id' => Hex::MAX_UINT128,
        ])->create();

        $encodedData = $this->codec->encode()->mint(
            $recipient = $this->recipient->public_key,
            $collectionId = $collection->collection_chain_id,
            $params = new MintParams(
                tokenId: $this->tokenIdEncoder->encode($token->token_chain_id),
                amount: fake()->numberBetween(),
            ),
        );

        $params = $params->toArray()['Mint'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable($token->token_chain_id);

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($recipient),
            'collectionId' => $collectionId,
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

    public function test_can_mint_a_token_with_not_existent_recipient_and_creates_it(): void
    {
        Wallet::where('public_key', '=', $recipient = app(Generator::class)->public_key())?->delete();

        $encodedData = $this->codec->encode()->mint(
            $recipient,
            $collectionId = $this->collection->collection_chain_id,
            $params = new MintParams(
                tokenId: $this->tokenIdEncoder->encode(),
                amount: fake()->numberBetween(),
            ),
        );

        $params = $params->toArray()['Mint'];
        $params['tokenId'] = $this->tokenIdEncoder->toEncodable();

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($recipient),
            'collectionId' => $collectionId,
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

        $this > $this->assertDatabaseHas('wallets', [
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

        $this->assertArraySubset(
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

        $this->assertArraySubset(
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
