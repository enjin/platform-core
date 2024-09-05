<?php

use Enjin\Platform\Enums\Global\ChainType;
use Enjin\Platform\Enums\Global\NetworkType;

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

if (!function_exists('isRunningLatest')) {
    /**
     * Check if the network is matrix.
     */
    function isRunningLatest(): bool
    {
        return networkConfig('spec-version') >= 1010;
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

if (!function_exists('specForBlock')) {
    /**
     * Get the spec version for a matrixchain block.
     */
    function specForBlock(?int $block = null, ?string $network = null): int
    {
        if ($block === null && $network === null) {
            return networkConfig('spec-version');
        }

        if ($block === null) {
            return networkConfig('spec-version', NetworkType::tryFrom($network) ?? network());
        }

        $network = NetworkType::tryFrom($network) ?? network();

        $runtime = collect(config(sprintf('enjin-runtime.%s', $network->value)))
            ->sortByDesc('blockNumber')
            ->filter(fn ($runtime) => $block >= $runtime['blockNumber'])
            ->first();

        return $runtime['specVersion'] ?? networkConfig('spec-version', $network);
    }
}

if (!function_exists('currentMatrixRuntime')) {
    /**
     * Get current runtime being used at matrixchain.
     */
    function currentMatrixRuntime(): array
    {
        return config(sprintf('enjin-runtime.%s.%d', network()->value, networkConfig('spec-version'))) ?? [];
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
