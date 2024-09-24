<?php

namespace Enjin\Platform\Tests\Support;

use Enjin\Platform\Support\JSON;
use Enjin\Platform\Support\Util;
use Mockery;
use WebSocket\Client;

trait MocksSocketClient
{
    protected function mockFee(array $mockedFee): void
    {
        $this->mockWebsocketClient(
            'payment_queryFeeDetails',
            [],
            json_encode(
                [
                    'jsonrpc' => '2.0',
                    'result' => [
                        'inclusionFee' => $mockedFee,
                    ],
                    'id' => 1,
                ],
                JSON_THROW_ON_ERROR
            ),
            true,
        );
    }

    /**
     * @throws \JsonException
     */
    protected function assertRpcResponseEquals(string $expected, string $actual)
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
    private function mockWebsocketClient(string $method, array $params, string $responseJson, bool $anyParam = false): void
    {
        $expectedRpcRequest = Util::createJsonRpc($method, $params);

        app()->bind(Client::class, function () use ($expectedRpcRequest, $responseJson, $anyParam) {
            $mock = Mockery::mock(Client::class);
            if ($anyParam) {
                $mock->shouldReceive('send')
                    ->once()
                    ->withAnyArgs();
            } else {
                $mock->shouldReceive('send')
                    ->once()
                    ->with(Mockery::on(function ($rpcRequest) use ($expectedRpcRequest) {
                        $this->assertRequestEquals($expectedRpcRequest, $rpcRequest);

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
     * @throws \JsonException
     */
    private function mockWebsocketClientSequence(array $responseSequence): void
    {
        app()->bind(Client::class, function () use ($responseSequence) {
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
