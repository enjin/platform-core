<?php

namespace Enjin\Platform\Tests;

use Enjin\Platform\CoreServiceProvider;
use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Tests\Feature\GraphQL\Traits\HasConvertableObject;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use HasConvertableObject;

    protected bool $fakeEvents = true;

    protected function getPackageProviders($app): array
    {
        return [
            CoreServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Make sure, our .env file is loaded for local tests
        $app->useEnvironmentPath(__DIR__ . '/..');
        $app->useDatabasePath(__DIR__ . '/../database');
        $app->bootstrapWith([LoadEnvironmentVariables::class]);

        Cache::rememberForever(PlatformCache::SPEC_VERSION->key(currentMatrix()->value), fn () => 1023);
        Cache::rememberForever(PlatformCache::TRANSACTION_VERSION->key(currentMatrix()->value), fn () => 10);

        $app['config']->set('database.default', env('DB_DRIVER', 'mysql'));

        // MySQL config
        $app['config']->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', 'password'),
            'database' => env('DB_DATABASE', 'platform'),
            'port' => env('DB_PORT', '3306'),
            'prefix' => '',
        ]);

        if ($this->fakeEvents) {
            Event::fake();
        }
    }

    protected function usesNullDaemonAccount($app): void
    {
        $app->config->set('enjin-platform.chains.daemon-account', '0x0000000000000000000000000000000000000000000000000000000000000000');
    }

    protected function usesEnjinNetwork($app): void
    {
        $app->config->set('enjin-platform.chains.network', 'enjin');
    }

    protected function usesCanaryNetwork($app): void
    {
        $app->config->set('enjin-platform.chains.network', 'canary');
    }

    protected function usesLocalNetwork($app): void
    {
        $app->config->set('enjin-platform.chains.network', 'local');
    }

    protected function assertArrayContainsArray(array $expected, array $actual): void
    {
        $this->assertArrayIsEqualToArrayOnlyConsideringListOfKeys($expected, $actual, $this->arrayKeys($expected));
    }

    protected function arrayKeys($array): array
    {
        return array_keys(Arr::dot($array));
    }
}
