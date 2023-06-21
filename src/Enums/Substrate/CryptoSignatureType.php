<?php

namespace Enjin\Platform\Enums\Substrate;

use Enjin\Platform\Traits\EnumExtensions;

enum CryptoSignatureType: string
{
    use EnumExtensions;

    case ED25519 = 'ed25519';
    case SR25519 = 'sr25519';
}
