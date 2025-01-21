<?php

namespace Enjin\Platform\Support;

use Enjin\Platform\Clients\Implementations\SubstrateSocketClient;
use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class Util
{
    /**
     * @throws PlatformException
     */
    public static function updateRuntimeVersion(?string $hash = null): array
    {
        $client = new Substrate(new SubstrateSocketClient());

        if (!$hash) {
            $block = Block::where('synced', true)->orderByDesc('number')->first();
            $hash = $block?->hash;
        }

        $runtime = $client->callMethod('state_getRuntimeVersion', $hash ? [$hash] : []);
        $specVersion = Arr::get($runtime, 'specVersion');
        $transactionVersion = Arr::get($runtime, 'transactionVersion');

        if (!$specVersion || !$transactionVersion) {
            throw new PlatformException(__('enjin-platform::error.runtime_version_not_found'));
        }

        Cache::rememberForever(PlatformCache::SPEC_VERSION->key(), fn () => $specVersion);
        Cache::rememberForever(PlatformCache::TRANSACTION_VERSION->key(), fn () => $transactionVersion);

        return [
            PlatformCache::SPEC_VERSION->key() => $transactionVersion,
            PlatformCache::TRANSACTION_VERSION->key() => $specVersion,
        ];
    }

    /**
     * Create a JSON-RPC encoded string.
     */
    public static function createJsonRpc(string $method, array $params = []): string
    {
        return json_encode([
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => random_int(1, 999999999),
        ], JSON_THROW_ON_ERROR);
    }

    public static function isBase64String(string $string)
    {
        return base64_encode(base64_decode($string, true)) === $string;
    }
}
