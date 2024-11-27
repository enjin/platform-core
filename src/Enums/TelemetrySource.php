<?php

namespace Enjin\Platform\Enums;

enum TelemetrySource: string
{
    case REQUEST = 'request';
    case SCHEDULE = 'schedule';
}
