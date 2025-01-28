<?php

use Enjin\Platform\Enums\Global\ChainType;
use Enjin\Platform\Enums\Global\NetworkType;
use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Support\Util;

if (!function_exists('network')) {
    /**
     * Get the network.
     */
    function network(): NetworkType
    {
        $network = config('enjin-platform.chains.network');

        if ($network === 'enjin') {
            return NetworkType::ENJIN_MATRIX;
        }

        if ($network === 'canary') {
            return NetworkType::CANARY_MATRIX;
        }

        if ($network === 'local') {
            return NetworkType::LOCAL_MATRIX;
        }

        return NetworkType::tryFrom($network) ?? NetworkType::ENJIN_MATRIX;
    }
}

if (!function_exists('isMainnet')) {
    /**
     * Check if the network is mainnet.
     */
    function isMainnet(): bool
    {
        return match (network()) {
            NetworkType::ENJIN_MATRIX, NetworkType::ENJIN_RELAY => true,
            default => false,
        };
    }
}

if (!function_exists('isTestnet')) {
    /**
     * Check if the network is testnet.
     */
    function isTestnet(): bool
    {
        return match (network()) {
            NetworkType::ENJIN_MATRIX, NetworkType::ENJIN_RELAY => false,
            default => true,
        };
    }
}

if (!function_exists('currentRelayUrl')) {
    /**
     * Get the equivalent relaychain url for the current used network.
     */
    function currentRelayUrl(): string
    {
        return networkConfig('node', currentRelay());
    }

}

if (!function_exists('currentMatrixUrl')) {
    /**
     * Get the equivalent matrixchain url for the current used network.
     */
    function currentMatrixUrl(): string
    {
        return networkConfig('node', currentMatrix());
    }

}

if (!function_exists('isMatrix')) {
    function isMatrix(NetworkType $network): bool
    {
        return $network === NetworkType::ENJIN_MATRIX || $network === NetworkType::CANARY_MATRIX;
    }
}

if (!function_exists('isRelay')) {
    function isRelay(NetworkType $network): bool
    {
        return $network === NetworkType::ENJIN_RELAY || $network === NetworkType::CANARY_RELAY;
    }
}

if (!function_exists('specForBlock')) {
    /**
     * Get the spec version for a matrixchain block.
     *
     * @throws Enjin\Platform\Exceptions\PlatformException
     */
    function specForBlock(?int $block = null, ?string $network = null): int
    {
        if ($block === null && $network === null) {
            return cachedRuntimeConfig(PlatformCache::SPEC_VERSION);
        }

        $network = NetworkType::tryFrom($network) ?? network();

        if ($block === null) {
            return isMatrix($network) ? cachedRuntimeConfig(PlatformCache::SPEC_VERSION) : networkConfig('spec-version', $network);
        }

        $runtime = collect(config(sprintf('enjin-runtime.%s', $network->value)))
            ->sortByDesc('blockNumber')
            ->filter(fn ($runtime) => $block >= $runtime['blockNumber'])
            ->first();

        return $runtime['specVersion'] ?? isMatrix($network) ? cachedRuntimeConfig(PlatformCache::SPEC_VERSION) : networkConfig('spec-version', $network);
    }
}

if (!function_exists('currentRelay')) {
    /**
     * Get the equivalent relaychain for the current used network.
     */
    function currentRelay(): NetworkType
    {
        return match (network()) {
            NetworkType::ENJIN_MATRIX, NetworkType::ENJIN_RELAY => NetworkType::ENJIN_RELAY,
            NetworkType::CANARY_MATRIX, NetworkType::CANARY_RELAY => NetworkType::CANARY_RELAY,
            NetworkType::LOCAL_MATRIX, NetworkType::LOCAL_RELAY => NetworkType::LOCAL_RELAY,
        };
    }
}

if (!function_exists('currentMatrix')) {
    /**
     * Get the equivalent matrixchain for the current used network.
     */
    function currentMatrix(): NetworkType
    {
        return match (network()) {
            NetworkType::ENJIN_MATRIX, NetworkType::ENJIN_RELAY => NetworkType::ENJIN_MATRIX,
            NetworkType::CANARY_MATRIX, NetworkType::CANARY_RELAY => NetworkType::CANARY_MATRIX,
            NetworkType::LOCAL_MATRIX, NetworkType::LOCAL_RELAY => NetworkType::LOCAL_MATRIX,
        };
    }
}


if (!function_exists('chain')) {
    /**
     * Get the chain.
     */
    function chain(): ChainType
    {
        return ChainType::tryFrom(config('enjin-platform.chains.selected')) ?? ChainType::SUBSTRATE;
    }
}

if (!function_exists('networkConfig')) {
    /**
     * Get the network config.
     */
    function networkConfig(string $config, ?NetworkType $network = null): mixed
    {
        return config(sprintf('enjin-platform.chains.supported.%s.%s.%s', chain()->value, $network?->value ?? network()->value, $config));
    }
}

if (!function_exists('cachedRuntimeConfig')) {
    /**
     * Get the cached network config.
     *
     * @throws Enjin\Platform\Exceptions\PlatformException
     */
    function cachedRuntimeConfig(PlatformCache $config): mixed
    {
        $value = Cache::get($config->key());

        if (!$value) {
            $value = Util::updateRuntimeVersion();

            return $value[$config->key()];
        }

        return $value;
    }
}
