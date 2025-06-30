<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Mutations;

use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Feature\GraphQL\Traits\HasHttp;
use Faker\Generator;
use Illuminate\Support\Collection;
use Override;

class MarkAndListPendingTransactionsTest extends TestCaseGraphQL
{
    use HasHttp;

    protected Collection $transactions;

    protected string $method = 'MarkAndListPendingTransactions';

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Transaction::query()->delete();

        $this->transactions = $this->generateTransactions();
    }

    #[Override]
    protected function tearDown(): void
    {
        Transaction::destroy($this->transactions);

        parent::tearDown();
    }

    // Exception Path
    public function test_it_can_fetch_with_no_args_without_auth(): void
    {
        $response = $this->httpGraphql($this->method);
        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_fetch_with_no_args(): void
    {
        $response = $this->graphql($this->method);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_fetch_with_empty_args(): void
    {
        $response = $this->graphql($this->method, []);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_fetch_with_null_wallet_addresses(): void
    {
        $response = $this->graphql($this->method, [
            'accounts' => null,
        ]);

        $this->assertTrue(count($response['edges']) > 0);
    }

    // Happy Path

    public function test_it_can_fetch_with_empty_wallet_addresses(): void
    {
        $response = $this->graphql($this->method, [
            'accounts' => [],
        ]);

        $this->assertTrue(count($response['edges']) > 0);
    }

    public function test_it_can_fetch_with_null_mark_as_processing(): void
    {
        $response = $this->graphql($this->method, [
            'markAsProcessing' => null,
        ]);

        $totalCount = $response['totalCount'];
        $this->generateTransactions();
        $response = $this->graphql($this->method, [
            'markAsProcessing' => true,
        ]);

        $this->assertTrue(
            $response['totalCount'] > 0
            && $totalCount > 0
            && $response['totalCount'] == $totalCount
        );
    }

    public function test_it_doesnt_return_a_processing_transaction(): void
    {
        $wallet = Wallet::factory([
            'managed' => true,
            'public_key' => app(Generator::class)->public_key(),
        ])->create();

        Transaction::factory([
            'state' => 'PROCESSING',
            'transaction_chain_id' => null,
            'transaction_chain_hash' => null,
            'wallet_public_key' => $wallet->public_key,
        ])->create();

        $response = $this->graphql($this->method);

        $this->assertTrue($response['totalCount'] == 5);
    }

    public function test_it_can_fetch_with_false_mark_as_processing(): void
    {
        $response = $this->graphql($this->method, [
            'markAsProcessing' => false,
        ]);

        $totalCount = $response['totalCount'];

        $response = $this->graphql($this->method, [
            'markAsProcessing' => false,
        ]);

        $this->assertTrue($totalCount > 0);
        $this->assertEquals($totalCount, $response['totalCount']);
    }

    public function test_it_can_fetch_with_null_after(): void
    {
        $response = $this->graphql($this->method, [
            'after' => null,
        ]);

        $this->assertTrue(count($response['edges']) > 0);
        $this->assertFalse($response['pageInfo']['hasPreviousPage']);
    }

    public function test_it_can_fetch_with_null_first(): void
    {
        $response = $this->graphql($this->method, [
            'first' => null,
            'markAsProcessing' => false,
        ]);

        $this->assertTrue(($totalItems = count($response['edges'])) > 0);

        $response = $this->graphql($this->method, [
            'markAsProcessing' => false,
        ]);

        $this->assertTrue($totalItems === count($response['edges']));
    }

    public function test_it_fetches_managed_wallets_tx_without_passing_their_address(): void
    {
        Transaction::query()->delete();
        Wallet::factory([
            'public_key' => $publicKey = app(Generator::class)->public_key(),
            'managed' => true,
        ])->create();

        Transaction::factory([
            'wallet_public_key' => $publicKey,
            'transaction_chain_id' => null,
            'transaction_chain_hash' => null,
        ])->create();

        $response = $this->graphql($this->method);

        $this->assertEquals(
            $publicKey,
            $response['edges'][0]['node']['wallet']['account']['publicKey']
        );
    }

    public function test_it_can_filter_transactions_by_address(): void
    {
        Wallet::factory([
            'public_key' => $publicKey = app(Generator::class)->public_key(),
            'managed' => true,
        ])->create();

        Transaction::factory([
            'wallet_public_key' => $publicKey,
            'transaction_chain_id' => null,
            'transaction_chain_hash' => null,
        ])->create();

        $response = $this->graphql('MarkAndListPendingTransactions', [
            'accounts' => [SS58Address::encode($publicKey)],
        ]);

        $this->assertTrue($response['totalCount'] === 1);
        $this->assertEquals(
            $publicKey,
            $response['edges'][0]['node']['wallet']['account']['publicKey']
        );
    }

    public function test_it_not_txs_will_appear_with_address_that_has_no_tx(): void
    {
        Wallet::where('public_key', '=', $publicKey = app(Generator::class)->public_key())?->delete();

        $response = $this->graphql('MarkAndListPendingTransactions', [
            'accounts' => [SS58Address::encode($publicKey)],
        ]);

        $this->assertTrue($response['totalCount'] === 0);
    }

    public function test_it_will_fail_with_invalid_mark_as_processing(): void
    {
        $response = $this->graphql($this->method, [
            'markAsProcessing' => 'invalid',
        ], true);

        $this->assertStringContainsString(
            'Variable "$markAsProcessing" got invalid value "invalid"; Boolean cannot represent a non boolean value',
            $response['error']
        );
    }

    public function test_it_will_fail_with_invalid_substrate_address(): void
    {
        $response = $this->graphql($this->method, [
            'accounts' => ['not_valid_address'],
        ], true);

        $this->assertArrayContainsArray(
            ['accounts.0' => ['The accounts.0 is not a valid substrate account.']],
            $response['error']
        );
    }

    protected function generateTransactions(?int $numberOfTransactions = 5): Collection
    {
        return collect(range(0, $numberOfTransactions - 1))
            ->map(fn () => Transaction::factory([
                'transaction_chain_id' => null,
                'transaction_chain_hash' => null,
            ])->create());
    }
}
