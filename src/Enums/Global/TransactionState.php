<?php

namespace Enjin\Platform\Enums\Global;

use Enjin\Platform\Traits\EnumExtensions;

enum TransactionState: string
{
    use EnumExtensions;

    case ABANDONED = 'Abandoned';
    case PENDING = 'Pending';
    case PROCESSING = 'Processing';
    case BROADCAST = 'Broadcast';
    case EXECUTED = 'Executed';
    case FINALIZED = 'Finalized';
}
