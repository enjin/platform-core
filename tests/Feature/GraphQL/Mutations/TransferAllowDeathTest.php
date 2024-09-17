<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\TransferBalanceMutation;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Support\MocksWebsocketClient;
use Faker\Generator;
use Illuminate\Support\Facades\Event;

class TransferAllowDeathTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;
    use MocksWebsocketClient;

    protected string $method = 'TransferAllowDeath';
    protected Codec $codec;
    protected string $defaultAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new Codec();
        $this->defaultAccount = Account::daemonPublicKey();
    }

    // Happy Path

    public function test_it_can_skip_validation(): void
    {
        Wallet::factory([
            'public_key' => $publicKey = app(Generator::class)->public_key(),
            'managed' => false,
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, TransferBalanceMutation::getEncodableParams(
            recipientAccount: $this->defaultAccount,
            value: $amount = fake()->numberBetween(),
        ));

        $this->mockFee($feeDetails = app(Generator::class)->fee_details());
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->defaultAccount),
            'amount' => $amount,
            'signingAccount' => SS58Address::encode($publicKey),
            'skipValidation' => true,
            'simulate' => true,
        ]);

        $this->assertArraySubset([
            'id' => null,
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'fee' => $feeDetails['fakeSum'],
            'deposit' => null,
            'wallet' => [
                'account' => [
                    'publicKey' => $publicKey,
                ],
            ],
        ], $response);

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_can_transfer_balance_with_ss58_signing_account(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, TransferBalanceMutation::getEncodableParams(
            recipientAccount: $publicKey = app(Generator::class)->public_key(),
            value: $amount = fake()->numberBetween(),
        ));

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($publicKey),
            'amount' => $amount,
            'signingAccount' => SS58Address::encode($signingAccount = app(Generator::class)->public_key),
        ]);

        $this->assertArraySubset([
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

    public function test_it_can_transfer_balance_with_bigint_amount(): void
    {
        if (static::class !== self::class) {
            $this->assertTrue(true);

            return;
        }

        $encodedData = TransactionSerializer::encode($this->method, TransferBalanceMutation::getEncodableParams(
            recipientAccount: $publicKey = app(Generator::class)->public_key(),
            value: $amount = Hex::MAX_UINT128
        ));

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($publicKey),
            'amount' => $amount,
        ]);

        $this->assertArraySubset([
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

    public function test_it_can_transfer_to_a_wallet_that_doesnt_exists(): void
    {
        Wallet::where('public_key', '=', $publicKey = app(Generator::class)->public_key())?->delete();

        $encodedData = TransactionSerializer::encode($this->method, TransferBalanceMutation::getEncodableParams(
            recipientAccount: $publicKey,
            value: $amount = fake()->numberBetween()
        ));

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($publicKey),
            'amount' => $amount,
        ]);

        $this->assertArraySubset([
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

        $this->assertDatabaseHas('wallets', [
            'public_key' => $publicKey,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_transfer_to_a_wallet_that_exists(): void
    {
        Wallet::factory([
            'public_key' => $publicKey = app(Generator::class)->public_key(),
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, TransferBalanceMutation::getEncodableParams(
            recipientAccount: $publicKey,
            value: $amount = fake()->numberBetween()
        ));

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($publicKey),
            'amount' => $amount,
        ]);

        $this->assertArraySubset([
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

    public function test_it_can_transfer_with_another_signing_wallet(): void
    {
        Wallet::factory([
            'public_key' => $publicKey = app(Generator::class)->public_key(),
            'managed' => true,
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, TransferBalanceMutation::getEncodableParams(
            recipientAccount: $this->defaultAccount,
            value: $amount = fake()->numberBetween()
        ));

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->defaultAccount),
            'amount' => $amount,
            'signingAccount' => SS58Address::encode($publicKey),
        ]);

        $this->assertArraySubset([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => [
                'account' => [
                    'publicKey' => $publicKey,
                ],
            ],
        ], $response);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_transfer_with_signing_wallet_null(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, TransferBalanceMutation::getEncodableParams(
            recipientAccount: $publicKey = app(Generator::class)->public_key(),
            value: $amount = fake()->numberBetween()
        ));

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($publicKey),
            'amount' => $amount,
            'signingAccount' => null,
        ]);

        $this->assertArraySubset([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_a_empty_signing_account_is_considered_the_daemon_account(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, TransferBalanceMutation::getEncodableParams(
            recipientAccount: $publicKey = app(Generator::class)->public_key(),
            value: $amount = fake()->numberBetween(),
        ));

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($publicKey),
            'amount' => $amount,
            'signingAccount' => '',
        ]);

        $this->assertArraySubset([
            'method' => $this->method,
            'state' => TransactionState::PENDING->name,
            'encodedData' => $encodedData,
            'wallet' => null,
        ], $response);

        Event::assertDispatched(TransactionCreated::class);
    }

    // Exception Path

    public function test_it_will_fail_with_no_args(): void
    {
        $response = $this->graphql($this->method, [], true);

        $this->assertStringContainsString(
            'Variable "$recipient" of required type "String!" was not provided.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_recipient(): void
    {
        $response = $this->graphql($this->method, [
            'amount' => fake()->numberBetween(),
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipient" of required type "String!" was not provided.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_no_amount(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode(app(Generator::class)->public_key()),
        ], true);

        $this->assertStringContainsString(
            'Variable "$amount" of required type "BigInt!" was not provided.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_recipient(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => null,
            'amount' => fake()->numberBetween(),
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipient" of non-null type "String!" must not be null.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_amount(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode(app(Generator::class)->public_key()),
            'amount' => null,
        ], true);

        $this->assertStringContainsString(
            'Variable "$amount" of non-null type "BigInt!" must not be null.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_recipient(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => 'not_valid',
            'amount' => fake()->numberBetween(),
        ], true);

        $this->assertArraySubset(
            ['recipient' => ['The recipient is not a valid substrate account.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_amount(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode(app(Generator::class)->public_key()),
            'amount' => 'not_valid',
        ], true);

        $this->assertStringContainsString(
            'Variable "$amount" got invalid value "not_valid"; Cannot represent following value as uint256',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_negative_amount(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode(app(Generator::class)->public_key()),
            'amount' => -1,
        ], true);

        $this->assertStringContainsString(
            'Variable "$amount" got invalid value -1; Cannot represent following value as uint256',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_signing_wallet(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode(app(Generator::class)->public_key()),
            'amount' => fake()->numberBetween(),
            'signingAccount' => 'not_valid',
        ], true);

        $this->assertArraySubset(
            ['signingAccount' => ['The signing account is not a valid substrate account.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
