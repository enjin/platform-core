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

if (!function_exists('mainnet')) {
    /**
     * Check if the network is mainnet.
     */
    function mainnet(): bool
    {
        return match (network()) {
            NetworkType::ENJIN_MATRIX, NetworkType::ENJIN_RELAY => true,
            default => false,
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
    function networkConfig(string $config): mixed
    {
        return config(sprintf('enjin-platform.chains.supported.%s.%s.%s', chain()->value, network()->value, $config));
    }
}
