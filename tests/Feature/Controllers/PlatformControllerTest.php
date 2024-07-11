<?php

namespace Enjin\Platform\Tests\Feature\Controllers;

use Enjin\Platform\Enums\Global\NetworkType;
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
        Cache::shouldReceive('remember')->andReturnUsing(fn (...$args) => []);

        $response = $this->json('GET', '/.well-known/enjin-platform.json');
        $this->assertTrue($response->isOk());
        $this->assertEquals(
            [
                'root' => 'enjin/platform-core',
                'url' => trim((string) config('app.url'), '/'),
                'chain' => chain()->value,
                'network' => network() === NetworkType::ENJIN_MATRIX ? 'enjin' : 'canary',
                'packages' => PlatformController::getPlatformPackages(),
                'release-diff' => [],
                'next-release' => [],
            ],
            $response->json()
        );
    }
}
