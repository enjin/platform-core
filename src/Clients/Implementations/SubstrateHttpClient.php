<?php

namespace Enjin\Platform\Clients\Implementations;

use Arr;
use Enjin\Platform\Clients\Abstracts\JsonHttpAbstract;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class SubstrateHttpClient extends JsonHttpAbstract
{
    protected string $url;
    protected ?PendingRequest $client = null;

    /**
     * Create a new http client instance.
     */
    public function __construct(?string $url = null)
    {
        $host = $url ?? currentMatrixUrl();

        $this->url = str_replace('wss', 'https', $host);
    }

    /**
     * Get the http client instance with keep-alive.
     */
    protected function getClient(): PendingRequest
    {
        if (!$this->client) {
            $this->client = Http::withHeaders([
                'Connection' => 'keep-alive',
                'Keep-Alive' => 'timeout=60, max=1000',
            ])
            ->retry(3, 500)
            ->timeout(60)
            ->asJson()
            ->acceptJson();
        }

        return $this->client;
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function jsonRpc(string $method, array $params): mixed
    {
        $result = $this->getClient()->post($this->url, [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => mt_rand(1, 999999999),
        ]);

        return Arr::get($this->getResponse($result), 'result');
    }
}
