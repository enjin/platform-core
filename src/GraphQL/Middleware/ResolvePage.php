<?php

namespace Enjin\Platform\GraphQL\Middleware;

use Closure;
use Enjin\Platform\Traits\HasFieldPagination;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Middleware;

class ResolvePage extends Middleware
{
    use HasFieldPagination;

    /**
     * Process the middleware.
     */
    public function handle($root, array $args, $context, ResolveInfo $info, Closure $next)
    {
        $this->resolveCurrentPageForPagination($args['after']);

        return $next($root, $args, $context, $info);
    }
}
