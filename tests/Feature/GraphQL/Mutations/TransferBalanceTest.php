<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Faker\Generator;
use Illuminate\Support\Facades\Event;

class TransferBalanceTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;

    protected string $method = 'TransferBalance';
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

        $encodedData = $this->codec->encode()->TransferBalance(
            $this->defaultAccount,
            $amount = fake()->numberBetween(),
        );

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->defaultAccount),
            'amount' => $amount,
            'signingAccount' => SS58Address::encode($publicKey),
            'skipValidation' => true,
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

    public function test_it_can_transfer_balance_without_keep_alive(): void
    {
        $encodedData = $this->codec->encode()->TransferBalance(
            $publicKey = app(Generator::class)->public_key(),
            $amount = fake()->numberBetween(),
        );

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($publicKey),
            'amount' => $amount,
            'keepAlive' => false,
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

    public function test_it_can_transfer_balance_with_bigint_amount(): void
    {
        $encodedData = $this->codec->encode()->TransferBalance(
            $publicKey = app(Generator::class)->public_key(),
            $amount = Hex::MAX_UINT128,
        );

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($publicKey),
            'amount' => $amount,
            'keepAlive' => false,
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

    public function test_it_can_transfer_balance_with_keep_alive(): void
    {
        $encodedData = $this->codec->encode()->TransferBalanceKeepAlive(
            $publicKey = app(Generator::class)->public_key(),
            $amount = fake()->numberBetween(),
        );

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($publicKey),
            'amount' => $amount,
            'keepAlive' => true,
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

    public function test_it_can_transfer_with_missing_keep_alive(): void
    {
        $encodedData = $this->codec->encode()->TransferBalance(
            $publicKey = app(Generator::class)->public_key(),
            $amount = fake()->numberBetween(),
        );

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($publicKey),
            'amount' => $amount,
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

    public function test_it_can_transfer_with_null_keep_alive(): void
    {
        $encodedData = $this->codec->encode()->TransferBalance(
            $publicKey = app(Generator::class)->public_key(),
            $amount = fake()->numberBetween(),
        );

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($publicKey),
            'amount' => $amount,
            'keepAlive' => null,
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

    public function test_it_can_transfer_to_a_wallet_that_doesnt_exists(): void
    {
        Wallet::where('public_key', '=', $publicKey = app(Generator::class)->public_key())?->delete();

        $encodedData = $this->codec->encode()->TransferBalance(
            $publicKey,
            $amount = fake()->numberBetween(),
        );

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($publicKey),
            'amount' => $amount,
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

        $encodedData = $this->codec->encode()->TransferBalance(
            $publicKey,
            $amount = fake()->numberBetween(),
        );

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($publicKey),
            'amount' => $amount,
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

    public function test_it_can_transfer_with_another_signing_wallet(): void
    {
        Wallet::factory([
            'public_key' => $publicKey = app(Generator::class)->public_key(),
            'managed' => true,
        ])->create();

        $encodedData = $this->codec->encode()->TransferBalance(
            $this->defaultAccount,
            $amount = fake()->numberBetween(),
        );

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
        $encodedData = $this->codec->encode()->TransferBalance(
            $publicKey = app(Generator::class)->public_key(),
            $amount = fake()->numberBetween(),
        );

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($publicKey),
            'amount' => $amount,
            'signingAccount' => null,
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

    public function test_it_will_fail_with_zero_amount(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode(app(Generator::class)->public_key()),
            'amount' => 0,
        ], true);

        $this->assertArraySubset(
            ['amount' => ['The amount is too small, the minimum value it can be is 1.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_keepalive(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode(app(Generator::class)->public_key()),
            'amount' => fake()->numberBetween(),
            'keepAlive' => 'not_valid',
        ], true);

        $this->assertStringContainsString(
            'Variable "$keepAlive" got invalid value "not_valid"; Boolean cannot represent a non boolean value',
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

    public function test_it_will_fail_with_empty_string_signing_wallet(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode(app(Generator::class)->public_key()),
            'amount' => fake()->numberBetween(),
            'signingAccount' => '',
        ], true);

        $this->assertArraySubset(
            ['signingAccount' => ['The signing account field must have a value.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_signing_wallet_not_saved(): void
    {
        Wallet::where('public_key', '=', $publicKey = app(Generator::class)->public_key())?->delete();

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->defaultAccount),
            'amount' => fake()->numberBetween(),
            'signingAccount' => SS58Address::encode($publicKey),
        ], true);

        $this->assertArraySubset(
            ['signingAccount' => ['The signing account is not a wallet managed by this platform.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_signing_wallet_that_is_not_managed(): void
    {
        Wallet::factory([
            'public_key' => $publicKey = app(Generator::class)->public_key(),
            'managed' => false,
        ])->create();

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->defaultAccount),
            'amount' => fake()->numberBetween(),
            'signingAccount' => SS58Address::encode($publicKey),
        ], true);

        $this->assertArraySubset(
            ['signingAccount' => ['The signing account is not a wallet managed by this platform.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
