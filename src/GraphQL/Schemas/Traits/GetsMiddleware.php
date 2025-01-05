<?php

namespace Enjin\Platform\GraphQL\Schemas\Traits;

trait GetsMiddleware
{
    protected function getMiddleware(): array
    {
        $parentMiddleware = get_class_vars(parent::class);
        $this->middleware = array_merge($this->middleware, $parentMiddleware['middleware']);

        if ($resolverMiddleware = config('graphql.resolver_middleware')) {
            $this->middleware = array_merge($this->middleware, $resolverMiddleware[class_basename(static::class)] ?? []);
            $this->middleware = array_merge($this->middleware, $resolverMiddleware[class_basename(get_parent_class(static::class))] ?? []);
        }

        $this->middleware = array_unique($this->middleware);

        return parent::getMiddleware();
    }
}
