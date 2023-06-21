<?php

namespace Enjin\Platform\Interfaces;

use Closure;

interface PlatformModelScope
{
    public static function applyTo(): array;

    public static function getName(): string;

    public static function getScope(): Closure;
}
