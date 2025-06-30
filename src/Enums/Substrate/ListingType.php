<?php

namespace Enjin\Platform\Enums\Substrate;

use Enjin\Platform\Traits\EnumExtensions;

enum ListingType: string
{
    use EnumExtensions;

    case FIXED_PRICE = 'FixedPrice';
    case AUCTION = 'Auction';
    case OFFER = 'Offer';
}
