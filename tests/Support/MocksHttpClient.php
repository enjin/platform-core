<?php

namespace Enjin\Platform\Tests\Support;

use Enjin\Platform\Support\JSON;
use Enjin\Platform\Support\Util;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

trait MocksHttpClient
{
    private function mockHttpClient(string $method, array $params, string $responseJson, bool $anyParam = false): void
    {
        $expectedRpcRequest = Util::createJsonRpc($method, $params);

        Http::fake([
            '*' => Http::response($responseJson),
        ]);

        Http::assertSent(function (Request $request) use ($expectedRpcRequest) {

            ray($request);
            ray($expectedRpcRequest);

            return $this->assertRequestEquals($expectedRpcRequest, json_encode($request->data()));
        });
    }

    private function mockHttpClientSequence(array $responseSequence): void
    {
        Http::fake(fn (Request $request) => Http::response(array_shift($responseSequence)));
    }

    /**
     * @throws \JsonException
     */
    protected function assertRequestEquals(string $expected, string $actual): bool
    {
        $expected = JSON::decode($expected, true, 512, JSON_THROW_ON_ERROR);
        unset($expected['id']);

        $actual = JSON::decode($actual, true, 512, JSON_THROW_ON_ERROR);
        unset($actual['id']);

        return $expected === $actual;
    }
}
