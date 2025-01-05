<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;

class CreateWalletTest extends TestCaseGraphQL
{
    // Happy Path

    public function test_create_ask_to_create_a_wallet_with_single_word(): void
    {
        Wallet::where('external_id', '=', $externalId = fake()->uuid())?->delete();

        $response = $this->graphql('CreateWallet', [
            'externalId' => $externalId,
        ]);

        $this->assertTrue($response);
        $this->assertDatabaseHas('wallets', [
            'public_key' => null,
            'external_id' => $externalId,
            'managed' => true,
        ]);
    }

    public function test_create_ask_to_create_a_wallet_with_multiple_words(): void
    {
        Wallet::where('external_id', '=', $externalId = fake()->uuid())?->delete();

        $response = $this->graphql('CreateWallet', [
            'externalId' => $externalId,
        ]);

        $this->assertTrue($response);
        $this->assertDatabaseHas('wallets', [
            'public_key' => null,
            'external_id' => $externalId,
            'managed' => true,
        ]);
    }

    public function test_create_ask_to_create_a_wallet_with_ascii(): void
    {
        Wallet::where('external_id', '=', $externalId = fake()->uuid())?->delete();

        $response = $this->graphql('CreateWallet', [
            'externalId' => $externalId,
        ]);

        $this->assertTrue($response);
        $this->assertDatabaseHas('wallets', [
            'public_key' => null,
            'external_id' => $externalId,
            'managed' => true,
        ]);
    }

    // Exception Path

    public function test_it_fail_if_external_id_is_not_unique(): void
    {
        Wallet::factory([
            'external_id' => $externalId = fake()->uuid(),
        ])->create();

        $response = $this->graphql('CreateWallet', [
            'externalId' => $externalId,
        ], true);

        $this->assertArrayContainsArray(
            ['externalId' => ['The external id has already been taken.']],
            $response['error'],
        );
    }

    public function test_it_fail_no_external_id(): void
    {
        $response = $this->graphql('CreateWallet', [], true);

        $this->assertStringContainsString(
            'Variable "$externalId" of required type "String!" was not provided.',
            $response['error'],
        );
    }

    public function test_it_fail_null_external_id(): void
    {
        $response = $this->graphql('CreateWallet', [
            'externalId' => null,
        ], true);

        $this->assertStringContainsString(
            'Variable "$externalId" of non-null type "String!" must not be null.',
            $response['error'],
        );
    }
}
