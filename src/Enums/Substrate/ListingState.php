<?php

namespace Enjin\Platform\Enums\Substrate;

use Enjin\Platform\Traits\EnumExtensions;

enum ListingState: string
{
    use EnumExtensions;

    case ACTIVE = 'Active';
    case CANCELLED = 'Cancelled';
    case FINALIZED = 'Finalized';
}
