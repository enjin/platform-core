<?php

namespace Enjin\Platform\Services\Serialization\Interfaces;

interface SerializationServiceInterface
{
    /**
     * Encode the given data.
     */
    public function encode(string $method, array $data, ?string $address = null): string;

    /**
     * Decode the encoded data.
     */
    public function decode(string $method, string $data): mixed;

    /**
     * Get method from encoded data.
     */
    public function getMethodFromEncoded(string $data): string;
}
