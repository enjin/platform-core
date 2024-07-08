<?php

namespace Enjin\Platform\Services\Serialization\Interfaces;

interface SerializationServiceInterface
{
    public function encodeRaw(string $type, array $data): string;

    /**
     * Encode the given data.
     */
    public function encode(string $method, array $data, ?string $address = null): string;

    /**
     * Decode the encoded data.
     */
    public function decode(string $method, string $data): mixed;
}
