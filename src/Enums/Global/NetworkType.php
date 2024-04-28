<?php

namespace Enjin\Platform\Enums\Global;

use Enjin\Platform\Traits\EnumExtensions;

enum NetworkType: string
{
    use EnumExtensions;

    case ENJIN_RELAY = 'enjin-relaychain';
    case ENJIN_MATRIX = 'enjin-matrixchain';
    case CANARY_RELAY = 'canary-relaychain';
    case CANARY_MATRIX = 'canary-matrixchain';
    case LOCAL_RELAY = 'local-relaychain';
    case LOCAL_MATRIX = 'local-matrixchain';
}
