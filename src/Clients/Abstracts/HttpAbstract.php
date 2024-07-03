<?php

namespace Enjin\Platform\Clients\Abstracts;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

abstract class HttpAbstract
{
    /**
     * Get the http client instance.
     */
    protected function getClient(): PendingRequest
    {
        return Http::retry(3, 500)->timeout(60)->withoutVerifying()->asJson()->acceptJson();
    }

    /**
     * Get the response data.
     *
     * @throws RequestException
     */
    protected function getResponse(Response|PromiseInterface $response): mixed
    {
        return $response instanceof Response ?
            $response->throw()->body() :
            $response;
    }
}
