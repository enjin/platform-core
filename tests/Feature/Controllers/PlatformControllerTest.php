<?php

namespace Enjin\Platform\Tests\Feature\Controllers;

use Enjin\Platform\Http\Controllers\PlatformController;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PlatformControllerTest extends TestCaseGraphQL
{
    public function test_it_can_get_platform_info(): void
    {
        Http::fake([
            config('enjin-platform.github.api_url') . '*' => Http::response([]),
        ]);
        Cache::shouldReceive('remember')->andReturnUsing(function (...$args) {
            return [];
        });

        $response = $this->json('GET', '/.well-known/enjin-platform.json');
        $this->assertTrue($response->isOk());
        $this->assertEquals(
            [
                'root' => 'enjin/platform-core',
                'url' => trim(config('app.url'), '/'),
                'chain' => config('enjin-platform.chains.selected'),
                'network' => config('enjin-platform.chains.network'),
                'packages' => PlatformController::getPlatformPackages(),
                'release-diff' => [],
                'next-release' => [],
            ],
            $response->json()
        );
    }
}
