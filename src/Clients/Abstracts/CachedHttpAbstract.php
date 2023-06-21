<?php

namespace Enjin\Platform\Clients\Abstracts;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\LaravelCacheStorage;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;

abstract class CachedHttpAbstract extends JsonHttpAbstract
{
    /**
     * Get the http client instance.
     */
    protected function getClient(): PendingRequest
    {
        $stack = HandlerStack::create();
        $stack->push(
            new CacheMiddleware(
                new PrivateCacheStrategy(
                    new LaravelCacheStorage(
                        Cache::store('redis')
                    )
                )
            ),
            'cache'
        );

        return parent::getClient()->setHandler($stack);
    }

    /**
     * Get the response data.
     *
     * @throws RequestException
     */
    protected function getResponse(Response|PromiseInterface $response): mixed
    {
        return parent::getResponse($response);
    }
}
