<?php

namespace Enjin\Platform\Services\Token;

use GraphQL\Type\Definition\Type;

interface Encoder
{
    /**
     * Get the name of the encoder.
     */
    public static function getName(): string;

    /**
     * Get the type of the encoder.
     */
    public static function getType(): Type;

    /**
     * Get the description of the encoder.
     */
    public static function getDescription(): string;

    /**
     * Get the rules of the encoder.
     */
    public static function getRules(): array;

    /**
     * Encode array of data into a tokenId.
     */
    public function encode(mixed $data): string;

    /**
     * Decode a tokenId into an array of data.
     */
    public function toEncodable(mixed $data = null): array;
}
