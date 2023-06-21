<?php

namespace Enjin\Platform\GraphQL\Schemas\Traits;

trait GetsMiddleware
{
    protected function getMiddleware(): array
    {
        if ($resolverMiddleware = config('graphql.resolver_middleware')) {
            $this->middleware = array_merge($this->middleware, $resolverMiddleware[class_basename(static::class)] ?? []);
            $this->middleware = array_merge($this->middleware, $resolverMiddleware[class_basename(get_parent_class(static::class))] ?? []);
        }

        return parent::getMiddleware();
    }
}
