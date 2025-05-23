<?php

namespace Enjin\Platform\Enums\Substrate;

use Enjin\Platform\Traits\EnumExtensions;

enum RuleType: string
{
    use EnumExtensions;

    case ACCOUNT = 'Account';
    case DISPATCH = 'Dispatch';
}
