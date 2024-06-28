<?php

namespace Enjin\Platform\Support;

class Util
{
    /**
     * Create a JSON-RPC encoded string.
     */
    public static function createJsonRpc(string $method, array $params = [], ?int $id = null): string
    {
        return json_encode([
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => $id ?? random_int(1, 999999999),
        ], JSON_THROW_ON_ERROR);
    }

    public static function isBase64String(string $string)
    {
        return base64_encode(base64_decode($string, true)) === $string;
    }
}
