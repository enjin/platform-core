<?php

namespace Enjin\Platform\Tests\Support\Mocks;

class StorageMock
{
    public static function null_account_storage()
    {
        return self::jsonRpcIt(null);
    }

    public static function account_with_balance()
    {
        return self::jsonRpcIt('0x000000000000000001000000000000000000c84e676dc11b0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000');
    }

    public static function fee(array $mockedFee)
    {
        return self::jsonRpcIt([
            'inclusionFee' => $mockedFee,
        ]);
    }

    protected static function jsonRpcIt(array|string|null $result)
    {
        return json_encode([
            'jsonrpc' => '2.0',
            'result' => $result,
            'id' => 1,
        ], JSON_THROW_ON_ERROR);
    }
}
