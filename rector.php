<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodeQuality\Rector\ClassConstFetch\ConvertStaticPrivateConstantToSelfRector;
use Rector\Php70\Rector\StaticCall\StaticCallOnNonStaticToInstanceCallRector;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use Rector\Set\ValueObject\LevelSetList;
use RectorLaravel\Set\LaravelSetList;

return static function (RectorConfig $config): void {
    $config->paths([
        __DIR__ . '/config',
        __DIR__ . '/lang',
        __DIR__ . '/resources',
        __DIR__ . '/routes',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    $config->rule(InlineConstructorDefaultToPropertyRector::class);
    $config->rule(Rector\Php83\Rector\ClassConst\AddTypeToConstRector::class);
    $config->rule(ConvertStaticPrivateConstantToSelfRector::class);
    $config->rule(Spatie\Ray\Rector\RemoveRayCallRector::class);

    $config->sets([
        LevelSetList::UP_TO_PHP_84,
        LaravelSetList::LARAVEL_120,
    ]);

    $config->skip([
        FirstClassCallableRector::class,
        StaticCallOnNonStaticToInstanceCallRector::class,
    ]);
};
