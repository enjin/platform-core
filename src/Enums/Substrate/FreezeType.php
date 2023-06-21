<?php

namespace Enjin\Platform\Enums\Substrate;

use Enjin\Platform\Traits\EnumExtensions;

enum FreezeType: string
{
    use EnumExtensions;

    case COLLECTION = 'Collection';
    case COLLECTION_ACCOUNT = 'CollectionAccount';
    case TOKEN = 'Token';
    case TOKEN_ACCOUNT = 'TokenAccount';
}
