<?php

namespace Enjin\Platform\Services\Serialization\Implementations;

use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use Illuminate\Support\Str;

class Substrate implements SerializationServiceInterface
{
    /**
     * Create a new instance.
     */
    public function __construct(protected Codec $codec = new Codec())
    {
    }

    /**
     * Encode the given data.
     */
    public function encode(string $method, array $data, $address = null): string
    {
        if (!$this->codec->encoder()->methodSupported($method)) {
            throw new PlatformException(__('enjin-platform::error.serialization.method_does_not_exist', ['method' => $method]), 403);
        }

        return $this->codec->encoder()->getEncoded($method, $data);
    }

    /**
     * Decode the encoded data.
     */
    public function decode(string $method, string $data): mixed
    {
        $method = Str::camel($method);

        if (!method_exists($this->codec->decoder(), $method)) {
            throw new PlatformException(__('enjin-platform::error.serialization.method_does_not_exist', ['method' => $method]), 403);
        }

        return $this->codec->decoder()->{$method}($data);
    }
}
