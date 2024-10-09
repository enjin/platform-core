<?php

namespace Enjin\Platform\Tests\Support;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

trait MocksHttpClient
{
    protected function mockFee(array $mockedFee): void
    {
        $this->mockHttpClient(
            json_encode(
                [
                    'jsonrpc' => '2.0',
                    'result' => [
                        'inclusionFee' => $mockedFee,
                    ],
                    'id' => 1,
                ],
                JSON_THROW_ON_ERROR
            )
        );
    }

    protected function mockHttpClient(string $responseJson): void
    {
        Http::fake([
            '*' => Http::response($responseJson),
        ]);
    }

    protected function mockHttpClientSequence(array $responseSequence): void
    {
        Http::fake(fn (Request $request) => Http::response(array_shift($responseSequence)));
    }
}
