<?php

namespace Enjin\Platform\Tests\Support;

use Enjin\Platform\Support\JSON;
use Enjin\Platform\Support\Util;
use Mockery;
use WebSocket\Client;
use WebSocket\Message\Text;

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
     * @throws \JsonException
     */
    protected function mockWebsocketClient(string $method, array $params, string $responseJson, bool $anyParam = false): void
    {
        $expectedRpcRequest = Util::createJsonRpc($method, $params);

        app()->bind(Client::class, function () use ($expectedRpcRequest, $responseJson, $anyParam) {
            $mock = Mockery::mock(Client::class);
            if ($anyParam) {
                $mock->shouldReceive('text')
                    ->once()
                    ->withAnyArgs();
            } else {
                $mock->shouldReceive('text')
                    ->once()
                    ->with(Mockery::on(function ($rpcRequest) use ($expectedRpcRequest) {
                        $this->assertRpcResponseEquals($expectedRpcRequest, $rpcRequest);

                        return true;
                    }));
            }

            $mock->shouldReceive('receive')
                ->once()
                ->andReturn(new Text($responseJson));

            $mock->shouldReceive('setTimeout')
                ->once()
                ->withArgs([30])
                ->andReturnSelf();

            $mock->shouldReceive('addMiddleware')
                ->twice()
                ->withAnyArgs()
                ->andReturnSelf();

            $mock->shouldReceive('isConnected')
                ->zeroOrMoreTimes()
                ->andReturn(true);

            return $mock;
        });
    }

    protected function mockWebsocketClientSequence(array $responseSequence): void
    {
        app()->bind(Client::class, function () use ($responseSequence) {
            $mock = Mockery::mock(Client::class);

            $mock->shouldReceive('isConnected')
                ->zeroOrMoreTimes()
                ->andReturn(true);

            $mock->shouldReceive('text')
                ->zeroOrMoreTimes()
                ->withAnyArgs();

            $mock->shouldReceive('receive')
                ->zeroOrMoreTimes()
                ->andReturnValues(array_map(fn ($response) => new Text($response), $responseSequence));

            $mock->shouldReceive('setTimeout')
                ->zeroOrMoreTimes()
                ->withArgs([30])
                ->andReturnSelf();

            $mock->shouldReceive('addMiddleware')
                ->zeroOrMoreTimes()
                ->withAnyArgs()
                ->andReturnSelf();

            return $mock;
        });
    }
}
