<?php

namespace Enjin\Platform\Enums\Substrate;

use Enjin\Platform\Traits\EnumExtensions;
use GMP;

enum TransactionDeposit: string
{
    use EnumExtensions;

    case COLLECTION = '6250000000000000000';
    case TOKEN_ACCOUNT = '10000000000000000';
    case ATTRIBUTE_BASE = '50000000000000000';
    case ATTRIBUTE_PER_BYTE = '25000000000000';
    case LISTING = '507225000000000000';
    case FUEL_TANK = '2500000000000000000';

    public function toGMP(): GMP
    {
        return TransactionDeposit::getGMP($this);
    }

    public static function getGMP(self $value): GMP
    {
        return match ($value) {
            self::COLLECTION => gmp_init(self::COLLECTION->value),
            self::TOKEN_ACCOUNT => gmp_init(self::TOKEN_ACCOUNT->value),
            self::ATTRIBUTE_BASE => gmp_init(self::ATTRIBUTE_BASE->value),
            self::ATTRIBUTE_PER_BYTE => gmp_init(self::ATTRIBUTE_PER_BYTE->value),
            self::LISTING => gmp_init(self::LISTING->value),
            self::FUEL_TANK => gmp_init(self::FUEL_TANK->value),
        };
    }
}
