<?php

namespace Enjin\Platform\Enums\Substrate;

use Enjin\Platform\Traits\EnumExtensions;

enum SystemEventType: string
{
    use EnumExtensions;

    case EXTRINSIC_SUCCESS = 'ExtrinsicSuccess';
    case EXTRINSIC_FAILED = 'ExtrinsicFailed';

    /**
     * Get the processor for the event.
     */
    public function getProcessor(): void {}
}
