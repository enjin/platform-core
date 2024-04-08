<?php

namespace Enjin\Platform\Enums\Substrate;

use Enjin\Platform\Traits\EnumExtensions;

enum TokenMintCapType: string
{
    use EnumExtensions;

    case SINGLE_MINT = 'SingleMint';
    case SUPPLY = 'Supply';
    case COLLAPSING_SUPPLY = 'CollapsingSupply';
    case INFINITE = 'Infinite';
}
