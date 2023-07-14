<?php

namespace Enjin\Platform\Tests\Unit;

use Enjin\Platform\Services\Auth\AuthManager;
use Enjin\Platform\Services\Auth\Drivers\BasicTokenAuth;
use Enjin\Platform\Services\Auth\Drivers\NullAuth;
use Enjin\Platform\Tests\TestCase;
use Illuminate\Support\Str;

class AuthServiceTest extends TestCase
{
    public function test_it_can_make_basic_auth()
    {
        $manager = resolve(AuthManager::class);
        $manager->setDefaultDriver('basic_token');
        $this->assertEquals($manager->getDefaultDriver(), 'basic_token');

        config(['enjin-platform.auth_drivers.basic_token.token' =>  $token = Str::random(20)]);
        $auth = $manager->driver();
        $this->assertInstanceOf(BasicTokenAuth::class, $auth);
        $this->assertEquals($token, $auth->getToken());

        $request = request();
        $request->initialize([], [], [], [], [], ['HTTP_AUTHORIZATION' => $token]);
        $this->assertTrue($auth->authenticate($request));
    }

    public function test_it_can_make_null_auth()
    {
        $auth = resolve(AuthManager::class)->driver();
        $this->assertInstanceOf(NullAuth::class, $auth);
        $this->assertEmpty($auth->getToken());
        $this->assertTrue($auth->authenticate(request()));
    }
}
