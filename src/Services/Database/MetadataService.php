<?php

namespace Enjin\Platform\Services\Database;

use Enjin\Platform\Clients\Implementations\MetadataHttpClient;
use Enjin\Platform\Models\Laravel\Attribute;
use Illuminate\Support\Facades\Cache;

class MetadataService
{
    public static $cacheKey = 'platform:attributeMetadata';

    /**
     * Create a new instance.
     */
    public function __construct(protected MetadataHttpClient $client) {}

    /**
     * Fetch the metadata from the attribute URL.
     */
    public function fetch(?Attribute $attribute): mixed
    {
        if (!filter_var($attribute?->value, FILTER_VALIDATE_URL)) {
            return null;
        }

        $response = $this->client->fetch($attribute->value);

        return $response ?: null;
    }

    public function fetchAndCache(?Attribute $attribute): mixed
    {
        if (!filter_var($attribute?->value, FILTER_VALIDATE_URL)) {
            return null;
        }

        return Cache::rememberForever(
            $this->cacheKey($attribute->value),
            fn () => $this->fetch($attribute)
        );
    }

    public function getCache(Attribute $attribute): mixed
    {
        return Cache::get($this->cacheKey($attribute->value), $this->fetchAndCache($attribute));
    }

    protected function cacheKey(string $suffix): string
    {
        return self::$cacheKey . ':' . $suffix;
    }
}
