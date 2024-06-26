<?php

namespace Enjin\Platform\Enums\Global;

use Enjin\Platform\Models\Collection;
use Enjin\Platform\Traits\EnumExtensions;

enum ModelType: string
{
    use EnumExtensions;

    case COLLECTION = Collection::class;
}
