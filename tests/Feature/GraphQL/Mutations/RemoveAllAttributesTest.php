<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Models\Attribute;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Services\Database\WalletService;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Token\Encoder;
use Enjin\Platform\Services\Token\Encoders\Integer;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

class RemoveAllAttributesTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;

    protected string $method = 'RemoveAllAttributes';
    protected Codec $codec;
    protected string $defaultAccount;
    protected Model $collection;
    protected Model $token;
    protected Encoder $tokenIdEncoder;
    protected Model $attribute;
    protected MOdel $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new Codec();
        $this->defaultAccount = Account::daemonPublicKey();
        $this->wallet = (new WalletService())->firstOrStore(['public_key' => $this->defaultAccount]);

        $this->attribute = Attribute::factory()->create();
        $this->collection = Collection::find($this->attribute->collection_id);
        $this->collection->update(['owner_wallet_id' => $this->wallet->id]);
        $this->token = Token::find($this->attribute->token_id);
        $this->token->update(['collection_id' => $this->collection->id]);
        $this->tokenIdEncoder = new Integer($this->token->token_chain_id);
    }

    public function test_it_can_skip_validation(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = random_int(1, 1000),
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'attributeCount' => $attributeCount = 1,
            'skipValidation' => true,
        ]);

        $encodedData = $this->codec->encode()->removeAllAttributes(
            $collectionId,
            $this->tokenIdEncoder->encode(),
            $attributeCount,
        );

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

    public function test_it_can_remove_an_attribute(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'attributeCount' => $attributeCount = 1,
        ]);

        $encodedData = $this->codec->encode()->removeAllAttributes(
            $collectionId,
            $this->tokenIdEncoder->encode(),
            $attributeCount,
        );

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

    public function test_it_can_remove_an_attribute_with_empty_attribute_count(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'attributeCount' => null,
        ]);

        $encodedData = $this->codec->encode()->removeAllAttributes(
            $collectionId,
            $this->tokenIdEncoder->encode(),
            1,
        );

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

    public function test_it_can_remove_an_attribute_with_no_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $this->collection->collection_chain_id,
            'attributeCount' => 1,
        ]);

        $encodedData = $this->codec->encode()->removeAllAttributes(
            $collectionId,
            null,
            1,
        );

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

    public function test_it_can_remove_an_attribute_with_bigint_collection_id(): void
    {
        Collection::where('collection_chain_id', Hex::MAX_UINT128)->update(['collection_chain_id' => random_int(1, 1000)]);
        $collection = Collection::factory(['collection_chain_id' => Hex::MAX_UINT128, 'owner_wallet_id'=>$this->wallet->id])->create();
        $collectionId = $collection->collection_chain_id;

        $token = Token::factory(['collection_id' => $collection])->create();
        Attribute::factory(['collection_id' => $collection, 'token_id' => $token])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_chain_id),
            'attributeCount' => $attributeCount = 1,
        ]);

        $encodedData = $this->codec->encode()->removeAllAttributes(
            $collectionId,
            $this->tokenIdEncoder->encode($token->token_chain_id),
            $attributeCount,
        );

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

    public function test_it_can_remove_an_attribute_with_bigint_token_id(): void
    {
        $collection = Collection::factory(['owner_wallet_id'=>$this->wallet->id, 'collection_chain_id' => $collectionId = fake()->numberBetween(2000)])->create();
        Token::where('token_chain_id', Hex::MAX_UINT128)->update(['token_chain_id' => random_int(1, 1000)]);
        $token = Token::factory(['collection_id' => $collection, 'token_chain_id' => $tokenId = Hex::MAX_UINT128])->create();
        Attribute::factory(['collection_id' => $collection, 'token_id' => $token])->create();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId = $collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable($token->token_chain_id),
            'attributeCount' => $attributeCount = 1,
        ]);

        $encodedData = $this->codec->encode()->removeAllAttributes(
            $collectionId,
            $this->tokenIdEncoder->encode($token->token_chain_id),
            $attributeCount,
        );

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
    public function test_it_fail_with_for_collection_that_doesnt_exists(): void
    {
        Collection::where('collection_chain_id', '=', $collectionId = '123456')?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'attributeCount' => 1,
        ], true);

        $this->assertArraySubset(
            ['collectionId' => ['The selected collection id is invalid.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_for_token_that_doesnt_exists(): void
    {
        Token::where('token_chain_id', '=', $tokenId = fake()->numberBetween())?->delete();

        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => $this->tokenIdEncoder->toEncodable($tokenId),
            'attributeCount' => 1,
        ], true);

        $this->assertArraySubset(
            ['tokenId' => ['The token id does not exist in the specified collection.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_invalid_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => 'not_valid',
            'tokenId' => $this->token->token_chain_id,
            'attributeCount' => 1,
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" got invalid value "not_valid"; Cannot represent following value as uint256',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_invalid_token_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => $this->collection->collection_chain_id,
            'tokenId' => 'not_valid',
            'attributeCount' => 1,
        ], true);

        $this->assertStringContainsString(
            'Variable "$tokenId" got invalid value "not_valid"; Expected type "EncodableTokenIdInput" to be an object',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_no_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'tokenId' => $this->token->token_chain_id,
            'attributeCount' => 1,
        ], true);

        $this->assertEquals(
            'Variable "$collectionId" of required type "BigInt!" was not provided.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_fail_with_null_collection_id(): void
    {
        $response = $this->graphql($this->method, [
            'collectionId' => null,
            'tokenId' => $this->token->token_chain_id,
            'attributeCount' => 1,
        ], true);

        $this->assertStringContainsString(
            'Variable "$collectionId" of non-null type "BigInt!" must not be null.',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
