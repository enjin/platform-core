<?php

namespace Enjin\Platform\Tests;

use Enjin\Platform\CoreServiceProvider;
use Enjin\Platform\Tests\Feature\GraphQL\Traits\HasConvertableObject;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use HasConvertableObject;

    protected $fakeEvents = true;

    protected function getPackageProviders($app)
    {
        return [
            CoreServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        Cache::flush();

        // Make sure, our .env file is loaded for local tests
        $app->useEnvironmentPath(__DIR__ . '/..');
        $app->bootstrapWith([LoadEnvironmentVariables::class]);

        $app['config']->set('database.default', env('DB_DRIVER', 'mysql'));

        // MySQL config
        $app['config']->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1:3306'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', 'password'),
            'database' => env('DB_DATABASE', 'platform'),
            'prefix' => '',
        ]);

        if ($this->fakeEvents) {
            Event::fake();
        }
    }

    protected function usesNullDaemonAccount($app)
    {
        $app->config->set('enjin-platform.chains.daemon-account', '0x0000000000000000000000000000000000000000000000000000000000000000');
    }

    protected function usesEnjinNetwork($app)
    {
        $app->config->set('enjin-platform.chains.network', 'enjin');
    }

    protected function usesCanaryNetwork($app)
    {
        $app->config->set('enjin-platform.chains.network', 'canary');
    }

    protected function usesDeveloperNetwork($app)
    {
        $app->config->set('enjin-platform.chains.network', 'developer');
    }
}
