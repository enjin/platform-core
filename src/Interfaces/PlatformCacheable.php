<?php

namespace Enjin\Platform\Interfaces;

use Illuminate\Support\Collection;

interface PlatformCacheable
{
    public function key(): string;

    public static function clearable(): Collection;
}
