<?php

if (!function_exists('network')) {
    /**
     * Get the network.
     */
    function network(): string
    {
        return config('enjin-platform.chains.network');
    }
}

if (!function_exists('chain')) {
    /**
     * Get the chain.
     */
    function chain(): string
    {
        return config('enjin-platform.chains.selected');
    }
}

if (!function_exists('networkConfig')) {
    /**
     * Get the network config.
     */
    function networkConfig(string $config): mixed
    {
        return config(sprintf('enjin-platform.chains.supported.%s.%s.%s', chain(), network(), $config));
    }
}
