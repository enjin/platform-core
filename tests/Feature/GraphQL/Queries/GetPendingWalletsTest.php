<?php

namespace Enjin\Platform\Tests\Feature\GraphQL\Queries;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Enjin\Platform\Tests\Feature\GraphQL\Traits\HasHttp;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class GetPendingWalletsTest extends TestCaseGraphQL
{
    use ArraySubsetAsserts;
    use HasHttp;

    protected string $method = 'GetPendingWallets';
    protected Model $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wallet = Wallet::factory([
            'managed' => true,
            'public_key' => null,
        ])->create();
    }

    public function test_it_can_get_pending_wallets_without_auth(): void
    {
        $response = $this->httpGraphql($this->method);
        $this->assertArraySubset(
            [
                'id' => $this->wallet->id,
                'account' => [
                    'publicKey' => $this->wallet->public_key,
                ],
                'externalId' => $this->wallet->external_id,
                'managed' => $this->wallet->managed,
                'network' => $this->wallet->network,
            ],
            Arr::last($response['edges'])['node'],
        );
    }

    public function test_it_can_get_pending_wallets(): void
    {
        $response = $this->graphql($this->method, []);

        $this->assertArraySubset(
            [
                'id' => $this->wallet->id,
                'account' => [
                    'publicKey' => $this->wallet->public_key,
                ],
                'externalId' => $this->wallet->external_id,
                'managed' => $this->wallet->managed,
                'network' => $this->wallet->network,
            ],
            Arr::last($response['edges'])['node'],
        );
    }

    public function test_it_will_not_appear_just_created_wallet_that_is_not_managed(): void
    {
        Wallet::factory([
            'managed' => false,
            'public_key' => null,
        ])->create();

        $response = $this->graphql($this->method, []);

        $this->assertEmpty(array_filter(
            $response['edges'],
            fn ($wallet) => $wallet['node']['managed'] === false,
        ));
    }

    public function test_it_will_not_appear_a_just_created_managed_wallet_with_address(): void
    {
        Wallet::where('public_key', '=', $publicKey = app(Generator::class)->public_key())?->delete();
        Wallet::factory([
            'managed' => true,
            'public_key' => $publicKey,
        ])->create();

        $response = $this->graphql($this->method, []);

        $this->assertEmpty(array_filter(
            $response['edges'],
            fn ($wallet) => $wallet['node']['account']['publicKey'] === $publicKey,
        ));
    }
}
