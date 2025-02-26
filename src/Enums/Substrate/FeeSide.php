<?php

namespace Enjin\Platform\Enums\Substrate;

use Enjin\Platform\Traits\EnumExtensions;

enum FeeSide: string
{
    use EnumExtensions;

    case NO_FEE = 'NoFee';
    case MAKE_FEE = 'Make';
    case TAKE_FEE = 'Take';
}
