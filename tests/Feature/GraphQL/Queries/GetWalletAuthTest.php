<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Queries;

use Enjin\Platform\CoreServiceProvider;
use Enjin\Platform\Models\Verification;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Support\MocksSocketClient;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Rebing\GraphQL\GraphQLServiceProvider;

class GetWalletAuthTest extends TestCaseGraphQL
{
    use MocksSocketClient;

    protected string $method = 'GetWallet';
    protected Codec $codec;
    protected Model $verification;
    protected Model $wallet;
    protected string $token;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new Codec();
        $this->verification = Verification::factory([
            'public_key' => $address = app(Generator::class)->public_key(),
        ])->create();
        $this->wallet = Wallet::factory([
            'public_key' => $address,
            'verification_id' => $this->verification->verification_id,
        ])->create();
    }

    public function test_it_can_get_wallet_without_token(): void
    {
        $this->app['config']->set('enjin-platform.auth', null);
        $this->getWallet();
    }

    public function test_it_can_get_wallet_with_basic_token(): void
    {
        $this->getWallet();
    }

    protected function getWallet(): void
    {
        $this->mockNonceAndBalancesFor($this->wallet->public_key);
        $response = $this->httpGraphql(
            $this->method,
            ['variables' => ['account' => SS58Address::encode($this->wallet->public_key)]],
            ['Authorization' => $this->token]
        );

        $this->assertArrayContainsArray([
            'id' => $this->wallet->id,
            'account' => [
                'publicKey' => $this->wallet->public_key,
            ],
        ], $response);
    }

    protected function mockNonceAndBalancesFor(string $account): void
    {
        $this->mockWebsocketClient(
            'state_getStorage',
            [
                $this->codec->encoder()->systemAccountStorageKey($account),
            ],
            json_encode(
                [
                    'jsonrpc' => '2.0',
                    'result' => '0x1d000000000000000100000000000000331f60a549ec45201cd30000000000000080ebc061752bf3a5000000000000000000000000000000000000000000000000000000000000000000000000000000',
                    'id' => 1,
                ],
                JSON_THROW_ON_ERROR
            )
        );
    }

    #[\Override]
    protected function getPackageProviders($app): array
    {
        return [
            CoreServiceProvider::class,
            GraphQLServiceProvider::class,
        ];
    }

    #[\Override]
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set(
            'enjin-platform.auth_drivers.basic_token',
            $this->token = Str::random(20)
        );
    }
}
