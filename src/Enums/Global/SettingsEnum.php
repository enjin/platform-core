<?php

namespace Enjin\Platform\Enums\Global;

use Enjin\Platform\Traits\EnumExtensions;

enum SettingsEnum: string
{
    use EnumExtensions;

    case TELEMETRY_UUID = 'telemetry_uuid';
}
