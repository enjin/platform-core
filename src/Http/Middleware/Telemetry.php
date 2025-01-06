<?php

namespace Enjin\Platform\Http\Middleware;

use Closure;
use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Services\PhoneHomeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Telemetry
{
    public function __construct(protected PhoneHomeService $service) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->service->isEnabled()) {
            try {
                Cache::lock(PlatformCache::TELEMETRY_CACHE_LOCK->value, 900)->get(fn () => $this->service->phone());
            } catch (Throwable $e) {
                Log::error('Failed to send telemetry event.', ['message' => $e->getMessage()]);
            }
        }

        return $next($request);
    }
}
