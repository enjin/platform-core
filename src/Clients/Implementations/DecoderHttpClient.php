<?php

namespace Enjin\Platform\Clients\Implementations;

use Enjin\Platform\Clients\Abstracts\JsonHttpAbstract;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

class DecoderHttpClient extends JsonHttpAbstract
{
    /**
     * Get the http client instance.
     */
    public function getClient(): PendingRequest
    {
        return parent::getClient();
    }

    /**
     * Get the response data.
     */
    public function getResponse(Response|PromiseInterface $response): mixed
    {
        return parent::getResponse($response);
    }
}
