<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\InfuseMutation;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Services\Token\Encoder;
use Enjin\Platform\Services\Token\Encoders\Integer;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Support\MocksHttpClient;
use Facades\Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

class InfuseTest extends TestCaseGraphQL
{
    use MocksHttpClient;

    protected $method = 'Infuse';

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
        $this->tokenIdEncoder = new Integer($this->token->token_chain_id);
    }

    // Happy path
    public function test_it_can_infuse_a_token(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, InfuseMutation::getEncodableParams(
            collectionId: $collectionId = $this->collection->collection_chain_id,
            tokenId: $this->tokenIdEncoder->encode(),
            amount: $amount = fake()->numberBetween(),
        ));

        $response = $this->graphql($this->method, [
            'collectionId' => $collectionId,
            'tokenId' => $this->tokenIdEncoder->toEncodable(),
            'amount' => $amount,
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
}
