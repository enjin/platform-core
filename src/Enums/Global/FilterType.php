<?php

namespace Enjin\Platform\Enums\Global;

use Enjin\Platform\Traits\EnumExtensions;

enum FilterType: string
{
    use EnumExtensions;

    case AND = 'AND';
    case OR = 'OR';
}
