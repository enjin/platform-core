<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Feature\GraphQL\Traits\HasHttp;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;

class SetWalletAccountTest extends TestCaseGraphQL
{
    use HasHttp;

    protected string $method = 'SetWalletAccount';
    protected Model $wallet;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->wallet = Wallet::factory([
            'external_id' => fake()->uuid(),
            'public_key' => null,
            'managed' => true,
        ])->create();
    }

    // Happy Path
    public function test_it_can_update_wallet_with_id_without_auth(): void
    {
        Wallet::where('public_key', '=', $publicKey = app(Generator::class)->public_key)?->delete();

        $response = $this->httpGraphql(
            $this->method,
            ['variables' => ['id' => $this->wallet->id, 'account' => $publicKey]]
        );
        $this->assertTrue($response);
        $this->assertDatabaseHas('wallets', [
            'id' => $this->wallet->id,
            'external_id' => $this->wallet->external_id,
            'public_key' => $publicKey,
            'managed' => true,
        ]);
    }

    public function test_it_can_update_wallet_with_id(): void
    {
        Wallet::where('public_key', '=', $publicKey = app(Generator::class)->public_key())?->delete();

        $response = $this->graphql($this->method, [
            'id' => $this->wallet->id,
            'account' => SS58Address::encode($publicKey),
        ]);

        $this->assertTrue($response);
        $this->assertDatabaseHas('wallets', [
            'id' => $this->wallet->id,
            'external_id' => $this->wallet->external_id,
            'public_key' => $publicKey,
            'managed' => true,
        ]);
    }

    public function test_it_can_update_wallet_with_external_id(): void
    {
        Wallet::where('public_key', '=', $publicKey = app(Generator::class)->public_key())?->delete();

        $response = $this->graphql($this->method, [
            'externalId' => $this->wallet->external_id,
            'account' => SS58Address::encode($publicKey),
        ]);

        $this->assertTrue($response);
        $this->assertDatabaseHas('wallets', [
            'id' => $this->wallet->id,
            'external_id' => $this->wallet->external_id,
            'public_key' => $publicKey,
            'managed' => true,
        ]);
    }

    // Exception Path

    public function test_it_will_fail_with_no_id_and_external_id(): void
    {
        $wallet = Wallet::factory()->create();
        $response = $this->graphql($this->method, [
            'account' => $wallet->public_key,
        ], true);

        $this->assertArrayContainsArray(
            [
                'id' => ['The id field is required when external id is not present.'],
                'externalId' => ['The external id field is required when id is not present.'],
            ],
            $response['error']
        );

        $response = $this->graphql($this->method, [
            'id' => $wallet->id,
            'externalId' => $wallet->external_id,
            'account' => $wallet->public_key,
        ], true);
        $this->assertArrayContainsArray(
            [
                'id' => ['The id field prohibits external id from being present.'],
                'externalId' => ['The external id field prohibits id from being present.'],
            ],
            $response['error']
        );
    }

    public function test_it_will_fail_with_no_address(): void
    {
        $response = $this->graphql($this->method, [
            'id' => $this->wallet->id,
        ], true);

        $this->assertStringContainsString(
            'Variable "$account" of required type "String!" was not provided.',
            $response['error']
        );
    }

    public function test_it_will_fail_with_null_address(): void
    {
        $response = $this->graphql($this->method, [
            'id' => $this->wallet->id,
            'account' => null,
        ], true);

        $this->assertStringContainsString(
            'Variable "$account" of non-null type "String!" must not be null.',
            $response['error']
        );
    }

    public function test_it_will_fail_with_invalid_address(): void
    {
        $response = $this->graphql($this->method, [
            'id' => $this->wallet->id,
            'account' => 'invalid_address',
        ], true);

        $this->assertArrayContainsArray(
            ['account' => ['The account is not a valid substrate account.']],
            $response['error']
        );
    }

    public function test_it_will_fail_with_duplicated_address(): void
    {
        Wallet::factory([
            'public_key' => $publicKey = app(Generator::class)->public_key(),
        ])->create();

        $response = $this->graphql($this->method, [
            'id' => $this->wallet->id,
            'account' => SS58Address::encode($publicKey),
        ], true);

        $this->assertArrayContainsArray(
            ['account' => ['The account has already been taken.']],
            $response['error']
        );
    }

    public function test_it_will_fail_if_another_address_has_been_set(): void
    {
        $wallet = Wallet::factory([
            'public_key' => app(Generator::class)->unique()->public_key(),
        ])->create();

        $response = $this->graphql($this->method, [
            'id' => $wallet->id,
            'account' => SS58Address::encode(app(Generator::class)->unique()->public_key()),
        ], true);

        $this->assertStringContainsString(
            'The wallet account is immutable once set.',
            $response['error']
        );
    }
}
