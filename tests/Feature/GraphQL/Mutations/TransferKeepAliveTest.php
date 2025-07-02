<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\TransferBalanceMutation;
use Enjin\Platform\Models\Indexer\Account;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Support\MocksSocketClient;
use Faker\Generator;
use Http;
use Illuminate\Support\Facades\Event;
use Override;
use ReflectionObject;

class TransferKeepAliveTest extends TransferAllowDeathTest
{
    use MocksSocketClient;

    protected string $method = 'TransferKeepAlive';
    protected array $fee;

    #[Override]
    public function test_it_can_skip_validation(): void
    {
        Account::factory([
            'id' => $publicKey = app(Generator::class)->public_key(),
            //            'managed' => false,
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, TransferBalanceMutation::getEncodableParams(
            recipientAccount: $this->defaultAccount,
            value: $amount = fake()->numberBetween(),
        ));

        self::clearExistingFakes();

        $this->mockFee($this->fee = app(Generator::class)->fee_details());
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->defaultAccount),
            'amount' => $amount,
            'signingAccount' => SS58Address::encode($publicKey),
            'skipValidation' => true,
            'simulate' => true,
        ]);

        $this->assertArrayContainsArray([
            'id' => null,
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'deposit' => null,
            'fee' => $this->fee['fakeSum'],
            'wallet' => [
                'account' => [
                    'publicKey' => $publicKey,
                ],
            ],
        ], $response);

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_not_enough_amount(): void
    {
        // Mocked balance = 2000000000000000000
        Account::factory([
            'id' => $publicKey = app(Generator::class)->public_key(),
            //            'managed' => false,
        ])->create();

        $response = $this->graphql($this->method, [
            'recipient' => $publicKey,
            'amount' => '1950000000000000000',
        ], expectError: true);

        $this->assertArrayContainsArray([
            'amount' => [
                'The amount is not enough to keep the existential deposit of 100000000000000000.',
            ],
        ], $response['error']);

        Event::assertNotDispatched(TransactionCreated::class);
    }

    private static function clearExistingFakes(): void
    {
        $reflection = new ReflectionObject(Http::getFacadeRoot());
        $property = $reflection->getProperty('stubCallbacks');
        $property->setAccessible(true);
        $property->setValue(Http::getFacadeRoot(), collect());
    }
}
