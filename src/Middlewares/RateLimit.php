<?php

namespace Enjin\Platform\Middlewares;

use Closure;
use Enjin\Platform\Exceptions\PlatformException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;

class RateLimit
{
    /**
     * The names of the schemas that should not be protected.
     */
    protected array $except = [
        '__schema',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): JsonResponse|RedirectResponse|Response
    {
        if (config('enjin-platform.rate_limit.enabled')) {
            $key = 'api:' . ($request->user()?->id ?: $request->ip());
            if (RateLimiter::tooManyAttempts($key, config('enjin-platform.rate_limit.attempts'))) {
                throw new PlatformException(
                    __('enjin-platform::error.too_many_requests', ['num' => RateLimiter::availableIn($key)])
                );
            }

            RateLimiter::hit($key, config('enjin-platform.rate_limit.time'));
        }


        return $next($request);
    }
}
