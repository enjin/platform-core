<?php

namespace Enjin\Platform\Support;

class JSON
{
    /**
     * Decode a JSON string.
     */
    public static function decode(?string $data, ?bool $associative = false, int $depth = 512, int $flags = 0): mixed
    {
        if (!$data) {
            return null;
        }

        return json_decode($data, $associative, $depth, $flags);
    }
}
