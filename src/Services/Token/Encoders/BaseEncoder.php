<?php

namespace Enjin\Platform\Services\Token\Encoders;

use Enjin\Platform\Services\Token\Encoder;
use Illuminate\Support\Str;

abstract class BaseEncoder implements Encoder
{
    /**
     * Construct a new encoder.
     */
    public function __construct(protected mixed $data = null)
    {
    }

    /**
     * Get the name of the encoder.
     */
    public static function getName(): string
    {
        return Str::camel(class_basename(static::class));
    }

    /**
     * Get the rules of the encoder.
     */
    public static function getRules(): array
    {
        return ['filled'];
    }

    /**
     * Decode a tokenId into an array of data.
     */
    public function toEncodable($data = null): array
    {
        return [
            self::getName() => $data ?? $this->data,
        ];
    }
}
