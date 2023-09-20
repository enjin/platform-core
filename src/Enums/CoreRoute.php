<?php

namespace Enjin\Platform\Enums;

use Enjin\Platform\Traits\EnumExtensions;

enum CoreRoute: string
{
    use EnumExtensions;

    case PROOF = 'proof/{code}';

    case QR = 'qr';
}
