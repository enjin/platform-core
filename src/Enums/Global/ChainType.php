<?php

namespace Enjin\Platform\Enums\Global;

use Enjin\Platform\Traits\EnumExtensions;

enum ChainType: string
{
    use EnumExtensions;

    case SUBSTRATE = 'substrate';
}
