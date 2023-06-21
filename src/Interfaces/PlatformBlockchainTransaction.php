<?php

namespace Enjin\Platform\Interfaces;

interface PlatformBlockchainTransaction
{
    /**
     * Get the method name.
     */
    public function getMethodName(): string;

    /**
     * Get the mutation name.
     */
    public function getMutationName(): string;
}
