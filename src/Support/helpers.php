<?php

use Enjin\Platform\Enums\Global\ChainType;
use Enjin\Platform\Enums\Global\NetworkType;

if (!function_exists('network')) {
    /**
     * Get the network.
     */
    function network(): NetworkType
    {
        return NetworkType::tryFrom(config('enjin-platform.chains.network')) ?? NetworkType::ENJIN_MATRIX;
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
