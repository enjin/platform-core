<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Faker\Generator;
use Illuminate\Support\Facades\Event;

class TransferAllBalanceTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;

    protected string $method = 'TransferAllBalance';
    protected Codec $codec;
    protected string $defaultAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new Codec();
        $this->defaultAccount = config('enjin-platform.chains.daemon-account');
    }

    // Happy Path

    public function test_it_can_transfer_all_balance(): void
    {
        $encodedData = $this->codec->encode()->transferAllBalance(
            $address = app(Generator::class)->public_key(),
            $keepAlive = fake()->boolean(),
        );

        $response = $this->graphql($this->method, [
            'recipient' => $address,
            'keepAlive' => $keepAlive,
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

    public function test_it_can_transfer_all_with_missing_keep_alive(): void
    {
        $encodedData = $this->codec->encode()->transferAllBalance(
            $address = app(Generator::class)->public_key(),
        );

        $response = $this->graphql($this->method, [
            'recipient' => $address,
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

    public function test_it_can_transfer_all_with_null_keep_alive(): void
    {
        $encodedData = $this->codec->encode()->transferAllBalance(
            $address = app(Generator::class)->public_key(),
        );

        $response = $this->graphql($this->method, [
            'recipient' => $address,
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

    public function test_it_can_transfer_all_to_a_wallet_that_doesnt_exists(): void
    {
        Wallet::where('public_key', '=', $address = app(Generator::class)->public_key())?->delete();

        $encodedData = $this->codec->encode()->transferAllBalance(
            $address,
            $keepAlive = fake()->boolean(),
        );

        $response = $this->graphql($this->method, [
            'recipient' => $address,
            'keepAlive' => $keepAlive,
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
            'public_key' => $address,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_transfer_all_to_a_wallet_that_exists(): void
    {
        Wallet::factory([
            'public_key' => $publicKey = app(Generator::class)->public_key(),
        ])->create();

        $encodedData = $this->codec->encode()->transferAllBalance(
            $publicKey,
            $keepAlive = fake()->boolean(),
        );

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($publicKey),
            'keepAlive' => $keepAlive,
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

    public function test_it_can_transfer_all_with_another_signing_wallet(): void
    {
        Wallet::factory([
            'public_key' => $publicKey = app(Generator::class)->public_key(),
            'managed' => true,
        ])->create();

        $encodedData = $this->codec->encode()->transferAllBalance(
            $this->defaultAccount,
            $keepAlive = fake()->boolean(),
        );

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->defaultAccount),
            'keepAlive' => $keepAlive,
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

    public function test_it_can_transfer_all_passing_the_default_wallet_on_signing_wallet(): void
    {
        $encodedData = $this->codec->encode()->transferAllBalance(
            $this->defaultAccount,
            $keepAlive = fake()->boolean(),
        );

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->defaultAccount),
            'keepAlive' => $keepAlive,
            'signingAccount' => SS58Address::encode($this->defaultAccount),
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

    public function test_it_can_transfer_all_with_signing_wallet_null(): void
    {
        $encodedData = $this->codec->encode()->transferAllBalance(
            $publicKey = app(Generator::class)->public_key(),
            $keepAlive = fake()->boolean(),
        );

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($publicKey),
            'keepAlive' => $keepAlive,
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

    public function test_it_will_fail_with_no_recipient(): void
    {
        $response = $this->graphql($this->method, [], true);

        $this->assertStringContainsString(
            'Variable "$recipient" of required type "String!" was not provided.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_null_recipient(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => null,
        ], true);

        $this->assertStringContainsString(
            'Variable "$recipient" of non-null type "String!" must not be null.',
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_recipient(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => 'not_valid',
        ], true);

        $this->assertArraySubset(
            ['recipient' => ['The recipient is not a valid substrate account.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }

    public function test_it_will_fail_with_invalid_keepalive(): void
    {
        $response = $this->graphql($this->method, [
            'recipient' => app(Generator::class)->public_key(),
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
            'recipient' => app(Generator::class)->public_key(),
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
            'recipient' => app(Generator::class)->public_key(),
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
            'signingAccount' => SS58Address::encode($publicKey),
        ], true);

        $this->assertArraySubset(
            ['signingAccount' => ['The signing account is not a wallet managed by this platform.']],
            $response['error'],
        );

        Event::assertNotDispatched(TransactionCreated::class);
    }
}
