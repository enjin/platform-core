<?php

namespace Enjin\Platform\Interfaces;

interface PlatformGraphQlResolverMiddleware
{
    public static function registerOn(): array;

    public static function excludeFrom(): array;
}
