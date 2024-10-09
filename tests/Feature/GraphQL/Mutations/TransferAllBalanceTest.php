<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations\TransferAllBalanceMutation;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Support\MocksHttpClient;
use Facades\Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Faker\Generator;
use Illuminate\Support\Facades\Event;

class TransferAllBalanceTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;
    use MocksHttpClient;

    protected string $method = 'TransferAllBalance';
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

        $encodedData = TransactionSerializer::encode($this->method, TransferAllBalanceMutation::getEncodableParams(
            recipientAccount: $this->defaultAccount,
            keepAlive: $keepAlive = fake()->boolean(),
        ));

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($this->defaultAccount),
            'keepAlive' => $keepAlive,
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

    public function test_it_can_simulate(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, TransferAllBalanceMutation::getEncodableParams(
            recipientAccount: $address = app(Generator::class)->public_key(),
            keepAlive: $keepAlive = fake()->boolean(),
        ));

        $this->mockFee($feeDetails = app(Generator::class)->fee_details());
        $response = $this->graphql($this->method, [
            'recipient' => $address,
            'keepAlive' => $keepAlive,
            'simulate' => true,
        ]);

        $this->assertArraySubset([
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

    public function test_it_can_transfer_all_balance(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, TransferAllBalanceMutation::getEncodableParams(
            recipientAccount: $address = app(Generator::class)->public_key(),
            keepAlive: $keepAlive = fake()->boolean(),
        ));

        $response = $this->graphql($this->method, [
            'recipient' => $address,
            'keepAlive' => $keepAlive,
            'nonce' => $nonce = fake()->numberBetween(),
        ]);

        $this->assertArraySubset([
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

    public function test_it_can_transfer_all_balance_with_public_key_signing_account(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, TransferAllBalanceMutation::getEncodableParams(
            recipientAccount: $address = app(Generator::class)->public_key(),
            keepAlive: $keepAlive = fake()->boolean(),
        ));

        $response = $this->graphql($this->method, [
            'recipient' => $address,
            'keepAlive' => $keepAlive,
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

    public function test_it_can_transfer_all_with_missing_keep_alive(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, TransferAllBalanceMutation::getEncodableParams(
            recipientAccount: $address = app(Generator::class)->public_key()
        ));

        $response = $this->graphql($this->method, [
            'recipient' => $address,
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

    public function test_it_can_transfer_all_with_null_keep_alive(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, TransferAllBalanceMutation::getEncodableParams(
            recipientAccount: $address = app(Generator::class)->public_key()
        ));

        $response = $this->graphql($this->method, [
            'recipient' => $address,
            'keepAlive' => null,
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

    public function test_it_can_transfer_all_to_a_wallet_that_doesnt_exists(): void
    {
        Wallet::where('public_key', '=', $address = app(Generator::class)->public_key())?->delete();

        $encodedData = TransactionSerializer::encode($this->method, TransferAllBalanceMutation::getEncodableParams(
            recipientAccount: $address,
            keepAlive: $keepAlive = fake()->boolean(),
        ));

        $response = $this->graphql($this->method, [
            'recipient' => $address,
            'keepAlive' => $keepAlive,
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
            'public_key' => $address,
        ]);

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_it_can_transfer_all_to_a_wallet_that_exists(): void
    {
        Wallet::factory([
            'public_key' => $publicKey = app(Generator::class)->public_key(),
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, TransferAllBalanceMutation::getEncodableParams(
            recipientAccount: $publicKey,
            keepAlive: $keepAlive = fake()->boolean(),
        ));

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($publicKey),
            'keepAlive' => $keepAlive,
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

    public function test_it_can_transfer_all_with_another_signing_wallet(): void
    {
        Wallet::factory([
            'public_key' => $publicKey = app(Generator::class)->public_key(),
            'managed' => true,
        ])->create();

        $encodedData = TransactionSerializer::encode($this->method, TransferAllBalanceMutation::getEncodableParams(
            recipientAccount: $this->defaultAccount,
            keepAlive: $keepAlive = fake()->boolean(),
        ));

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

    public function test_it_can_transfer_all_with_signing_wallet_null(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, TransferAllBalanceMutation::getEncodableParams(
            recipientAccount: $publicKey = app(Generator::class)->public_key(),
            keepAlive: $keepAlive = fake()->boolean(),
        ));

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($publicKey),
            'keepAlive' => $keepAlive,
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

    public function test_it_can_transfer_all_with_signing_wallet_empty_and_works_as_daemon(): void
    {
        $encodedData = TransactionSerializer::encode($this->method, TransferAllBalanceMutation::getEncodableParams(
            recipientAccount: $publicKey = app(Generator::class)->public_key(),
            keepAlive: $keepAlive = fake()->boolean(),
        ));

        $response = $this->graphql($this->method, [
            'recipient' => SS58Address::encode($publicKey),
            'keepAlive' => $keepAlive,
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
}
