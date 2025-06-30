<?php

namespace Enjin\Platform\Tests\Support;

use Enjin\Platform\Support\JSON;
use Enjin\Platform\Support\Util;
use JsonException;
use Mockery;
use Random\RandomException;
use ReflectionException;
use WebSocket\Client;

trait MocksSocketClient
{
    protected function assertRpcResponseEquals(string $expected, string $actual): void
    {
        $expected = JSON::decode($expected, true, 512, JSON_THROW_ON_ERROR);
        unset($expected['id']);

        $actual = JSON::decode($actual, true, 512, JSON_THROW_ON_ERROR);
        unset($actual['id']);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws JsonException
     * @throws RandomException
     * @throws ReflectionException
     */
    protected function mockWebsocketClient(string $method, array $params, string $responseJson, bool $anyParam = false): void
    {
        $expectedRpcRequest = Util::createJsonRpc($method, $params);

        app()->bind(function () use ($expectedRpcRequest, $responseJson, $anyParam): Client {
            $mock = Mockery::mock(Client::class);
            if ($anyParam) {
                $mock->shouldReceive('send')
                    ->once()
                    ->withAnyArgs();
            } else {
                $mock->shouldReceive('send')
                    ->once()
                    ->with(Mockery::on(function ($rpcRequest) use ($expectedRpcRequest) {
                        $this->assertRpcResponseEquals($expectedRpcRequest, $rpcRequest);

                        return true;
                    }));
            }

            $mock->shouldReceive('receive')
                ->once()
                ->andReturn($responseJson);

            return $mock;
        });
    }

    /**
     * @throws ReflectionException
     */
    protected function mockWebsocketClientSequence(array $responseSequence): void
    {
        app()->bind(function () use ($responseSequence): Client {
            $mock = Mockery::mock(Client::class);

            $mock->shouldReceive('isConnected')
                ->zeroOrMoreTimes()
                ->andReturn(true);

            $mock->shouldReceive('send')
                ->zeroOrMoreTimes()
                ->withAnyArgs();

            $mock->shouldReceive('receive')
                ->zeroOrMoreTimes()
                ->andReturnValues($responseSequence);

            return $mock;
        });
    }
}
