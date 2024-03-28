<?php

namespace Enjin\Platform\Tests\Unit;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Clients\Implementations\SubstrateWebsocket;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Support\Mocks\StorageMock;
use Enjin\Platform\Tests\Support\MocksWebsocketClient;
use Enjin\Platform\Tests\TestCase;
use Faker\Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class WalletTest extends TestCase
{
    use ArraySubsetAsserts;
    use MocksWebsocketClient;
    use RefreshDatabase;

    protected Substrate $blockchainService;

    protected function setUp(): void
    {
        parent::setUp();

        $ws = new SubstrateWebsocket('ws://localhost');
        $this->blockchainService = new Substrate($ws);
    }

    public function test_it_returns_null_if_no_saved_wallet_and_no_address()
    {
        $result = $this->blockchainService->walletWithBalanceAndNonce(null);

        $this->assertNull($result);
    }

    public function test_it_returns_a_zero_balance_if_no_saved_wallet_and_no_storage_for_the_address()
    {
        $this->mockWebsocketClientSequence([
            StorageMock::null_account_storage(),
        ]);

        $result = $this->blockchainService->walletWithBalanceAndNonce(SS58Address::encode($publicKey = app(Generator::class)->public_key))->toArray();

        $this->assertArraySubset([
            'public_key' => $publicKey,
            'nonce' => 0,
            'balances' => [
                'free' => '0',
                'reserved' => '0',
                'miscFrozen' => '0',
                'feeFrozen' => '0',
            ],
        ], $result, true);

        $this->assertDatabaseHas('wallets', [
            'public_key' => $publicKey,
        ]);
    }

    public function test_it_returns_zero_balance_when_there_is_a_saved_wallet_and_no_storage()
    {
        $this->mockWebsocketClientSequence([
            StorageMock::null_account_storage(),
        ]);

        $wallet = new Wallet();
        $wallet->public_key = $publicKey = app(Generator::class)->public_key;
        $wallet->network = 'rococo';
        $wallet->external_id = $externalId = 'savedWallet';
        $wallet->save();

        $result = $this->blockchainService->walletWithBalanceAndNonce($wallet)->toArray();

        $this->assertArraySubset([
            'public_key' => $publicKey,
            'external_id' => $externalId,
            'network' => 'rococo',
            'nonce' => 0,
            'balances' => [
                'free' => '0',
                'reserved' => '0',
                'miscFrozen' => '0',
                'feeFrozen' => '0',
            ],
        ], $result, true);

        $this->assertDatabaseHas('wallets', [
            'public_key' => $publicKey,
            'external_id' => $externalId,
        ]);
    }

    public function test_it_returns_a_balance_when_no_saved_wallet_and_has_storage()
    {
        $this->mockWebsocketClientSequence([
            StorageMock::account_with_balance(),
        ]);

        $result = $this->blockchainService->walletWithBalanceAndNonce(SS58Address::encode($publicKey = app(Generator::class)->public_key))->toArray();

        $this->assertArraySubset([
            'public_key' => $publicKey,
            'nonce' => 0,
            'balances' => [
                'free' => '2000000000000000000',
                'reserved' => '0',
                'miscFrozen' => '0',
                'feeFrozen' => '0',
            ],
        ], $result, true);

        $this->assertDatabaseHas('wallets', [
            'public_key' => $publicKey,
        ]);
    }

    public function test_it_returns_a_balance_when_there_is_saved_wallet_and_has_storage()
    {
        $this->mockWebsocketClientSequence([
            StorageMock::account_with_balance(),
        ]);

        $wallet = new Wallet();
        $wallet->public_key = $publicKey = app(Generator::class)->public_key;
        $wallet->network = 'rococo';
        $wallet->external_id = $externalId = 'savedWallet2';
        $wallet->save();

        $result = $this->blockchainService->walletWithBalanceAndNonce($wallet)->toArray();

        $this->assertArraySubset([
            'public_key' => $publicKey,
            'network' => 'rococo',
            'external_id' => $externalId,
            'nonce' => 0,
            'balances' => [
                'free' => '2000000000000000000',
                'reserved' => '0',
                'miscFrozen' => '0',
                'feeFrozen' => '0',
            ],
        ], $result, true);

        $this->assertDatabaseHas('wallets', [
            'public_key' => $publicKey,
            'external_id' => $externalId,
        ]);
    }
}
