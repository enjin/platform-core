<?php

namespace Enjin\Platform\Clients\Abstracts;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Override;

abstract class JsonHttpAbstract extends HttpAbstract
{
    /**
     * Get the http client instance.
     */
    #[Override]
    protected function getClient(): PendingRequest
    {
        return parent::getClient()
            ->asJson()
            ->acceptJson();
    }

    /**
     * Get the response data.
     *
     * @throws RequestException
     */
    #[Override]
    protected function getResponse(Response|PromiseInterface $response): mixed
    {
        return $response instanceof Response ?
            $response->throw()->json() :
            $response;
    }
}
