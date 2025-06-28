<?php

namespace Enjin\Platform\Services\Serialization\Implementations;

use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;

class Substrate implements SerializationServiceInterface
{
    /**
     * Create a new instance.
     */
    public function __construct(protected Codec $codec = new Codec()) {}

    public function encodeRaw(string $type, array $data): string
    {
        return $this->codec->encoder()->encodeRaw($type, $data);
    }

    /**
     * Encode the given data.
     * @throws PlatformException
     */
    public function encode(string $method, array $data, $address = null): string
    {
        if (!$this->codec->encoder()->methodSupported($method)) {
            throw new PlatformException(__('enjin-platform::error.serialization.method_does_not_exist', ['method' => $method]), 403);
        }

        return $this->codec->encoder()->getEncoded($method, $data);
    }
}
