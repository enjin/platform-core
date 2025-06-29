<?php

namespace Enjin\Platform\GraphQL\Middleware;

use Closure;
use Enjin\Platform\Exceptions\PlatformException;
use GraphQL\Type\Definition\ResolveInfo;
use Override;
use Rebing\GraphQL\Support\Middleware;

class SingleArgOnly extends Middleware
{
    /**
     * Process the middleware.
     * @throws PlatformException
     */
    #[Override]
    public function handle($root, array $args, $context, ResolveInfo $info, Closure $next)
    {
        if (count($args) != 1) {
            throw new PlatformException(__('enjin-platform::error.middleware.single_arg_only'), 403);
        }

        return $next($root, $args, $context, $info);
    }
}
