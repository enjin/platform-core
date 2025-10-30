<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Events\Global\TransactionUpdated;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Support\MocksSocketClient;
use Enjin\Platform\Clients\Implementations\SubstrateHttpClient;
use Facades\Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Faker\Generator;
use Illuminate\Support\Facades\Event;
use Mockery;

class SendTransactionTest extends TestCaseGraphQL
{
    use MocksSocketClient;

    protected string $method = 'SendTransaction';

    protected array $payload;
    protected string $extrinsic;
    protected string $signature;
    protected string $hash;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->payload = $this->generatePayload();
        $this->signature = app(Generator::class)->signature;
        $this->extrinsic = $this->createExtrinsic($this->payload, $this->signature);
        $this->hash = app(Generator::class)->hash;
    }

    // Happy Path
    public function test_it_can_send_a_new_transaction(): void
    {
        $this->mockSubmitExtrinsic($this->extrinsic, $this->hash);

        $response = $this->graphql($this->method, [
            'signature' => $this->signature,
            'signingPayloadJson' => $this->payload,
        ]);

        $this->assertEquals($response, $this->hash);
        $this->assertDatabaseHas('transactions', [
            'transaction_chain_hash' => $this->hash,
            'state' => TransactionState::BROADCAST->name,
            'encoded_data' => $this->payload['method'],
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_send_a_new_transaction_with_id_null(): void
    {
        $this->mockSubmitExtrinsic($this->extrinsic, $this->hash);

        $response = $this->graphql($this->method, [
            'id' => null,
            'signature' => $this->signature,
            'signingPayloadJson' => $this->payload,
        ]);

        $this->assertEquals($response, $this->hash);
        $this->assertDatabaseHas('transactions', [
            'transaction_chain_hash' => $this->hash,
            'state' => TransactionState::BROADCAST->name,
            'encoded_data' => $this->payload['method'],
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_update_a_previously_created_transaction(): void
    {
        $transaction = Transaction::factory()->create([
            'encoded_data' => $this->payload['method'],
            'state' => TransactionState::PENDING->name,
        ]);

        $this->mockSubmitExtrinsic($this->extrinsic, $this->hash);

        $response = $this->graphql($this->method, [
            'id' => $transaction->id,
            'signature' => $this->signature,
            'signingPayloadJson' => $this->payload,
        ]);

        $this->assertEquals($response, $this->hash);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'transaction_chain_hash' => $this->hash,
            'state' => TransactionState::BROADCAST->name,
            'encoded_data' => $this->payload['method'],
            'wallet_public_key' => SS58Address::getPublicKey($this->payload['address']),
        ]);

        Event::assertDispatched(TransactionUpdated::class);
    }

    // Unhappy paths
    public function test_it_will_fail_with_invalid_id(): void
    {
        $response = $this->graphql($this->method, [
            'id' => 'INVALID',
            'signature' => $this->signature,
            'signingPayloadJson' => $this->payload,
        ], true);

        $this->assertStringContainsString(
            'Variable "$id" got invalid value "INVALID"; Int cannot represent non-integer value',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_signature(): void
    {
        $response = $this->graphql($this->method, [
            'signingPayloadJson' => $this->payload,
        ], true);

        $this->assertStringContainsString(
            'Variable "$signature" of required type "String!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_signature(): void
    {
        $response = $this->graphql($this->method, [
            'signature' => null,
            'signingPayloadJson' => $this->payload,
        ], true);

        $this->assertStringContainsString(
            'Variable "$signature" of non-null type "String!" must not be null',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_signature(): void
    {
        $response = $this->graphql($this->method, [
            'signature' => 'INVALID',
            'signingPayloadJson' => $this->payload,
        ], true);

        $this->assertArrayContainsArray(
            ['signature' => ['The signature has an invalid hex string.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_payload(): void
    {
        $response = $this->graphql($this->method, [
            'signature' => $this->signature,
        ], true);

        $this->assertStringContainsString(
            'Variable "$signingPayloadJson" of required type "Object!" was not provided',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_payload(): void
    {
        $response = $this->graphql($this->method, [
            'signature' => $this->signature,
            'signingPayloadJson' => null,
        ], true);

        $this->assertStringContainsString(
            'Variable "$signingPayloadJson" of non-null type "Object!" must not be null',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_payload(): void
    {
        $response = $this->graphql($this->method, [
            'signature' => $this->signature,
            'signingPayloadJson' => 'INVALID',
        ], true);

        $this->assertStringContainsString(
            'Variable "$signingPayloadJson" got invalid value',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_id_doesnt_exists(): void
    {
        Transaction::where(['id' => $id = fake()->randomDigit()])->delete();

        $response = $this->graphql($this->method, [
            'id' => $id,
            'signature' => $this->signature,
            'signingPayloadJson' => $this->payload,
        ], true);

        $this->assertArrayContainsArray(
            ['id' => ['The selected id is invalid.']],
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_rpc_response(): void
    {
        $this->mockSubmitExtrinsic($this->extrinsic, ['error' => ['message' => 'Blockchain error']]);

        $response = $this->graphql($this->method, [
            'signature' => $this->signature,
            'signingPayloadJson' => $this->payload,
        ], true);

        $this->assertStringContainsString(
            'Blockchain error',
            $response['error']
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    protected function createExtrinsic(array $payload, string $signature): string
    {
        return Substrate::createExtrinsic(
            $payload['address'],
            $signature,
            $payload['method'],
            $payload['nonce'],
            $payload['era'],
            $payload['tip'],
        );
    }

    protected function generatePayload(): array
    {
        return [
            'address' => app(Generator::class)->chain_address,
            'method' => '0x0a07009ee74b7fb517fc26778db942d381ca00a44a2d0a90c2838839a900c23330a31b13000064a7b3b6e00d',
            'nonce' => '0x00',
            'era' => '0x00',
            'tip' => '0x00',
        ];
    }

    protected function mockSubmitExtrinsic(string $extrinsic, array|string $response): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
        ];

        if (is_array($response)) {
            $json = array_merge($json, $response);
        } else {
            $json['result'] = $response;
        }

        // Mock the HTTP client instead of WebSocket client
        $mockHttpClient = Mockery::mock(SubstrateHttpClient::class);
        $mockHttpClient->shouldReceive('jsonRpc')
            ->once()
            ->with('author_submitExtrinsic', [$extrinsic], true)
            ->andReturn($json);

        app()->bind(SubstrateHttpClient::class, fn () => $mockHttpClient);
    }
}
