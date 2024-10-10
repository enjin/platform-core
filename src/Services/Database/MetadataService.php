<?php

namespace Enjin\Platform\Services\Database;

use Enjin\Platform\Clients\Implementations\MetadataHttpClient;
use Enjin\Platform\Events\Substrate\MultiTokens\MetadataUpdated;
use Enjin\Platform\Models\Laravel\Attribute;
use Enjin\Platform\Support\Hex;
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

    public function fetchUrl(string $url): mixed
    {
        $url = $this->convertHexToString($url);

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        return $this->client->fetch($url) ?: null;
    }

    public function fetchAttributeWithEvent(Attribute $attribute): mixed
    {
        $old = $this->getCache($url = $attribute->value_string);
        $new = $this->fetchAndCache($url);
        if ($old !== $new) {
            event(new MetadataUpdated(
                $attribute->collection->collection_chain_id,
                $attribute->token?->token_chain_id,
            ));
        }

        return $new;
    }

    public function fetchAndCache(string $url, bool $forget = true): mixed
    {
        $url = $this->convertHexToString($url);

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        if ($forget) {
            Cache::forget($this->cacheKey($url));
        }

        return Cache::rememberForever(
            $this->cacheKey($url),
            fn () => $this->fetchUrl($url)
        );
    }

    public function getCache(string $url): mixed
    {
        $url = $this->convertHexToString($url);

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        return Cache::get($this->cacheKey($url), $this->fetchAndCache($url, false));
    }

    protected function convertHexToString(string $url): string
    {
        return Hex::isHexEncoded($url) ? Hex::safeConvertToString($url) : $url;
    }

    protected function cacheKey(string $suffix): string
    {
        return self::$cacheKey . ':' . $suffix;
    }
}
