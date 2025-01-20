<?php

namespace Enjin\Platform\Enums\Substrate;

use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\System\CodeUpdated;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Enjin\Platform\Traits\EnumExtensions;

enum SystemEventType: string
{
    use EnumExtensions;

    case EXTRINSIC_SUCCESS = 'ExtrinsicSuccess';
    case EXTRINSIC_FAILED = 'ExtrinsicFailed';
    case CODE_UPDATED = 'CodeUpdated';

    /**
     * Get the processor for the event.
     */
    public function getProcessor($event, $block, $codec): ?SubstrateEvent
    {
        return match ($this) {
            self::CODE_UPDATED => new CodeUpdated($event, $block, $codec),
            default => null,
        };
    }
}
