<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Spatie\Ray\Rector\RemoveRayCallRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/lang',
        __DIR__ . '/resources',
        __DIR__ . '/routes',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php84: true)
    ->withPreparedSets(deadCode: true)
    ->withRules([RemoveRayCallRector::class])
    ->withTypeCoverageLevel(0);
