<?php

namespace Enjin\Platform\Clients\Implementations;

use Arr;
use Enjin\Platform\Clients\Abstracts\JsonHttpAbstract;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;

class SubstrateHttpClient extends JsonHttpAbstract
{
    protected string $url;
    protected ?PendingRequest $client = null;
    protected static ?HandlerStack $sharedHandler = null;

    /**
     * Create a new http client instance.
     */
    public function __construct(?string $url = null)
    {
        $host = $url ?? currentMatrixUrl();

        $this->url = str_replace('wss', 'https', $host);
    }

    #[\Override]
    protected function getClient(): PendingRequest
    {
        self::$sharedHandler ??= HandlerStack::create(new CurlHandler());

        return $this->client ??= parent::getClient()->withOptions([
            'handler' => self::$sharedHandler,
        ]);
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function jsonRpc(string $method, array $params): mixed
    {
        $response = $this->getClient()->post($this->url, [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => mt_rand(1, 999999999),
        ]);

        return Arr::get($this->getResponse($response), 'result');
    }
}
