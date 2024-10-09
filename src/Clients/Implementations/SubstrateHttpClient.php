<?php

namespace Enjin\Platform\Clients\Implementations;

use Arr;
use Enjin\Platform\Clients\Abstracts\JsonHttpAbstract;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class SubstrateHttpClient extends JsonHttpAbstract
{
    protected string $url;

    /**
     * Create a new http client instance.
     */
    public function __construct(?string $url = null)
    {
        $host = $url ?? currentMatrixUrl();

        $this->url = str_replace('wss', 'https', $host);
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
