<?php

namespace Enjin\Platform\GraphQL\Middleware;

use Closure;
use Enjin\Platform\Exceptions\PlatformException;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Middleware;

class SingleFilterOnly extends Middleware
{
    /**
     * Process the middleware.
     */
    public function handle($root, array $args, $context, ResolveInfo $info, Closure $next)
    {
        $filledArgs = collect($args)->filter(fn ($arg) => !empty($arg));
        $singleFilterArgs = collect($root->getAttributes()['args'])->where('singleFilter', true);
        $otherFilterArgs = collect($root->getAttributes()['args'])->where('filter', true);

        $filledSingleFilters = $filledArgs->intersectByKeys($singleFilterArgs);
        $filledOtherFilters = $filledArgs->intersectByKeys($otherFilterArgs);

        if ($filledSingleFilters->isNotEmpty() && $filledOtherFilters->isNotEmpty()) {
            $filterOptions = $singleFilterArgs->keys()->implode(', ');

            throw new PlatformException(__('enjin-platform::error.middleware.single_filter_only.only_used_alone', ['filterOptions' => $filterOptions]), 403);
        }

        if ($filledSingleFilters->count() > 1) {
            $filterOptions = $singleFilterArgs->keys()->implode(', ');

            throw new PlatformException(__('enjin-platform::error.middleware.single_filter_only.only_one_filter', ['filterOptions' => $filterOptions]), 403);
        }

        return $next($root, $args, $context, $info);
    }
}
