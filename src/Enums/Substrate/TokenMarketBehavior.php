<?php

namespace Enjin\Platform\Enums\Substrate;

use Enjin\Platform\Traits\EnumExtensions;

enum TokenMarketBehavior: string
{
    use EnumExtensions;

    case HAS_ROYALTY = 'HasRoyalty';
    case IS_CURRENCY = 'IsCurrency';
    case NONE = 'None';
}
