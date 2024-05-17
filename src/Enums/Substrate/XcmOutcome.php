<?php

namespace Enjin\Platform\Enums\Substrate;

use Enjin\Platform\Traits\EnumExtensions;

enum XcmOutcome: string
{
    use EnumExtensions;

    case COMPLETE = 'Complete';
    case INCOMPLETE = 'Incomplete';
}
