<?php

namespace Enjin\Platform\Tests\Feature\Controllers;

use Enjin\Platform\Http\Controllers\PlatformController;
use Enjin\Platform\Tests\Feature\GraphQL\TestCaseGraphQL;

class PlatformControllerTest extends TestCaseGraphQL
{
    public function test_it_can_get_platform_info(): void
    {
        $response = $this->json('GET', '/.well-known/enjin-platform.json');
        $this->assertTrue($response->isOk());
        $this->assertEquals(
            [
                'root' => 'enjin/platform-core',
                'url' => trim(config('app.url'), '/'),
                'chain' => config('enjin-platform.chains.selected'),
                'network' => config('enjin-platform.chains.network'),
                'packages' => PlatformController::getPlatformPackages(),
            ],
            $response->json()
        );
    }
}
