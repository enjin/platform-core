<?php

namespace Enjin\Platform\Enums\Substrate;

use Enjin\Platform\Traits\EnumExtensions;

enum FreezeStateType: string
{
    use EnumExtensions;

    case PERMANENT = 'Permanent';
    case TEMPORARY = 'Temporary';
    case NEVER = 'Never';
}
