<?php

namespace Enjin\Platform\Clients\Implementations;

use Enjin\Platform\Clients\Abstracts\JsonHttpAbstract;
use GuzzleHttp\Handler\CurlHandler;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;

class SubstrateHttpClient extends JsonHttpAbstract
{
    protected string $url;
    protected ?PendingRequest $client = null;
    protected static ?CurlHandler $sharedHandler = null;

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
    public function jsonRpc(string $method, array $params, bool $raw = false): mixed
    {
        $response = $this->getClient()->post($this->url, [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => mt_rand(1, 999999999),
        ]);

        $fullResponse = $this->getResponse($response);

        return $raw ? $fullResponse : Arr::get($fullResponse, 'result');
    }

    #[\Override]
    protected function getClient(): PendingRequest
    {
        self::$sharedHandler ??= new CurlHandler();

        return $this->client ??= parent::getClient()->setHandler(self::$sharedHandler);
    }
}
