<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;

class UpdateWalletExternalIdTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;

    protected string $method = 'UpdateWalletExternalId';
    protected Model $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wallet = Wallet::factory([
            'external_id' => fake()->uuid(),
            'public_key' =>  app(Generator::class)->public_key,
            'managed' => false,
        ])->create();
    }

    // Happy Path

    public function test_it_can_update_wallet_with_id(): void
    {
        $newExternalId = fake()->uuid();

        $response = $this->graphql($this->method, [
            'address' => null,
            'externalId' => null,
            'id' => $this->wallet->id,
            'newExternalId' => $newExternalId,
        ]);

        $this->assertTrue($response);
        $this->assertDatabaseHas('wallets', [
            'id' => $this->wallet->id,
            'external_id' => $newExternalId,
            'public_key' => $this->wallet->public_key,
            'managed' => false,
        ]);
    }

    public function test_it_can_update_wallet_with_empty_external_id(): void
    {
        $response = $this->graphql($this->method, [
            'account' => $this->wallet->public_key,
            'externalId' => null,
            'id' => null,
            'newExternalId' => '',
        ]);

        $this->assertTrue($response);
        $this->assertDatabaseHas('wallets', [
            'id' => $this->wallet->id,
            'external_id' => null,
            'public_key' => $this->wallet->public_key,
            'managed' => false,
        ]);
    }

    public function test_it_can_update_wallet_with_external_id(): void
    {
        $newExternalId = fake()->uuid();

        $response = $this->graphql($this->method, [
            'address' => null,
            'id' => null,
            'externalId' => $this->wallet->external_id,
            'newExternalId' => $newExternalId,
        ]);

        $this->assertTrue($response);
        $this->assertDatabaseHas('wallets', [
            'id' => $this->wallet->id,
            'external_id' => $newExternalId,
            'public_key' => $this->wallet->public_key,
            'managed' => false,
        ]);
    }

    public function test_it_can_update_wallet_with_address(): void
    {
        $newExternalId = fake()->uuid();

        $response = $this->graphql($this->method, [
            'externalId' => null,
            'id' => null,
            'account' => SS58Address::encode($this->wallet->public_key),
            'newExternalId' => $newExternalId,
        ]);

        $this->assertTrue($response);
        $this->assertDatabaseHas('wallets', [
            'id' => $this->wallet->id,
            'external_id' => $newExternalId,
            'public_key' => $this->wallet->public_key,
            'managed' => false,
        ]);
    }

    // Exception Path

    public function test_it_will_fail_with_managed_wallet(): void
    {
        $wallet = Wallet::factory([
            'external_id' => null,
            'managed' => true,
        ])->create();

        $response = $this->graphql($this->method, [
            'id' => $wallet->id,
            'newExternalId' => fake()->uuid(),
        ], true);

        $this->assertStringContainsString(
            'Cannot update the external id on a managed wallet.',
            $response['error']
        );
    }

    public function test_it_will_fail_with_missing_new_external_id(): void
    {
        $response = $this->graphql($this->method, [
            'id' => $this->wallet->id,
        ], true);

        $this->assertStringContainsString(
            'Variable "$newExternalId" of required type "String!" was not provided.',
            $response['error']
        );
    }

    public function test_it_will_fail_with_id_not_in_database(): void
    {
        $response = $this->graphql($this->method, [
            'id' => fake()->numberBetween(1000),
            'newExternalId' => fake()->uuid(),
        ], true);

        $this->assertArraySubset(
            ['id' => ['The selected id is invalid.']],
            $response['error']
        );
    }

    public function test_it_will_fail_with_external_id_not_in_database(): void
    {
        $response = $this->graphql($this->method, [
            'externalId' => fake()->uuid(),
            'newExternalId' => fake()->uuid(),
        ], true);

        $this->assertArraySubset(
            ['externalId' => ['The selected external id is invalid.']],
            $response['error']
        );
    }

    public function test_it_will_fail_with_address_not_in_database(): void
    {
        $response = $this->graphql($this->method, [
            'account' => SS58Address::encode(app(Generator::class)->public_key()),
            'newExternalId' => fake()->uuid(),
        ], true);

        $this->assertArraySubset(
            ['account' => ['Could not find the account specified.']],
            $response['error']
        );
    }

    public function test_it_will_fail_with_missing_or_null_filter(): void
    {
        $response = $this->graphql($this->method, [
            'account' => null,
            'newExternalId' => fake()->uuid(),
        ], true);

        $this->assertArraySubset(
            [
                'id' => ['The id field is required when none of external id / account are present.'],
                'externalId' => ['The external id field is required when none of id / account are present.'],
                'account' => ['The account field is required when none of id / external id are present.'],
            ],
            $response['error']
        );
    }

    public function test_it_will_fail_with_multiple_filters(): void
    {
        $response = $this->graphql($this->method, [
            'id' => $this->wallet->id,
            'externalId' => fake()->uuid(),
            'account' => app(Generator::class)->public_key,
            'newExternalId' => fake()->uuid(),
        ], true);

        $this->assertStringContainsString(
            'Only one of these filter(s) can be used: id, externalId, account',
            $response['error']
        );
    }

    public function test_it_will_fail_with_invalid_address(): void
    {
        $response = $this->graphql($this->method, [
            'account' => 'invalid_address',
            'newExternalId' => fake()->uuid(),
        ], true);


        $this->assertArraySubset(
            ['account' => ['The account is not a valid substrate account.']],
            $response['error']
        );
    }

    public function test_it_will_fail_with_existing_external_id(): void
    {
        $otherWallet = Wallet::factory([
            'external_id' => fake()->uuid(),
        ])->create();

        $response = $this->graphql($this->method, [
            'id' => $this->wallet->id,
            'newExternalId' => $otherWallet->external_id,
        ], true);

        $this->assertArraySubset(
            ['newExternalId' => ['The new external id has already been taken.']],
            $response['error']
        );
    }
}
