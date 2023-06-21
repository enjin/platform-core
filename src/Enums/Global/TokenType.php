<?php

namespace Enjin\Platform\Enums\Global;

use Enjin\Platform\Traits\EnumExtensions;

enum TokenType: string
{
    use EnumExtensions;

    case FUNGIBLE = 'Fungible';
    case NON_FUNGIBLE = 'NonFungible';
}
