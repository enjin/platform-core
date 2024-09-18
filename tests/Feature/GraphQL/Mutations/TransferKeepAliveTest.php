<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Tests\Support\Mocks\StorageMock;
use Enjin\Platform\Tests\Support\MocksWebsocketClient;
use Faker\Generator;
use Illuminate\Support\Facades\Event;

class TransferKeepAliveTest extends TransferAllowDeathTest
{
    use MocksWebsocketClient;

    protected string $method = 'TransferKeepAlive';

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockWebsocketClientSequence([
            StorageMock::account_with_balance(),
        ]);
    }

    public function test_it_will_fail_with_not_enough_amount(): void
    {
        // Mocked balance = 2000000000000000000
        Wallet::factory([
            'public_key' => $publicKey = app(Generator::class)->public_key(),
            'managed' => false,
        ])->create();

        $response = $this->graphql($this->method, [
            'recipient' => $publicKey,
            'amount' => '1950000000000000000',
        ], expectError: true);

        $this->assertArraySubset([
            'amount' => [
                'The amount is not enough to keep the existential deposit of 100000000000000000.',
            ],
        ], $response['error']);

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
