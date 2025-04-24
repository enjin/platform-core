<?php

namespace Enjin\Platform;

use Composer\InstalledVersions;
use Enjin\Platform\Enums\CoreRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Package
{
    public static $path;

    public static function getPath()
    {
        return self::$path;
    }

    public static function setPath(string $path, bool $overwrite = false)
    {
        self::$path = $overwrite ? $path : self::$path ?? $path;
    }

    public static function clearPath()
    {
        self::$path = null;
    }

    public static function getPathToVendorFolder()
    {
        $basePath = app()->basePath();

        return Str::before($basePath, 'vendor');
    }

    /**
     * Get the composer autoloader for auto-bootstrapping services.
     */
    public static function getAutoloader()
    {
        $vendorPath = self::$path ?? self::getPathToVendorFolder();
        $vendorPath = rtrim((string) $vendorPath, DIRECTORY_SEPARATOR);

        return require "{$vendorPath}/vendor/autoload.php";

    }

    /**
     * Get any routes that have been set up for this package.
     */
    public static function getPackageRoutes(): array
    {
        return CoreRoute::caseValuesAsArray();
    }

    /**
     * Get a list of package and app classes.
     */
    public static function getPackageClasses(): Collection
    {
        return collect(self::getAutoloader()->getClassMap())
            ->keys()
            ->filter(function ($className) {
                $appNamespace = trim((string) app()->getNamespace(), '\\');
                $namespaceFilter = "/^(Enjin\\\\Platform|{$appNamespace})\\\\/";

                return preg_match($namespaceFilter, $className)
                    && class_exists($className)
                    && !(new \ReflectionClass($className))->isAbstract();
            });
    }

    public static function getClass(string $className)
    {
        return self::getPackageClasses()->first(fn ($class) => Str::afterLast($class, '\\') == $className);
    }

    public static function classImplementsInterface($class, $interface)
    {
        return in_array($interface, class_implements($class));
    }

    /**
     * Get a list of classes that implement a specific interface.
     */
    public static function getClassesThatImplementInterface(string $interface): Collection
    {
        return self::getPackageClasses()->filter(fn ($className) => self::classImplementsInterface($className, $interface));
    }

    /**
     * Get a list of class names that implement a specific interface.
     */
    public static function getClassNamesThatImplementInterface(string $interface): Collection
    {
        return self::getClassesThatImplementInterface($interface)
            ->transform(fn ($class) => Str::afterLast($class, '\\'))
            ->unique();
    }

    /**
     * Get a list of GraphQL fields that implement a specific interface.
     */
    public static function getGraphQlFieldsThatImplementInterface(string $interface): Collection
    {
        return self::getClassNamesThatImplementInterface($interface)
            ->transform(fn ($class) => Str::replace(['Query', 'Mutation'], '', $class))
            ->unique();
    }

    public static function getInstalledPlatformPackages(): Collection
    {
        return collect(InstalledVersions::getInstalledPackages())
            ->filter(fn ($packageName) => preg_match("/^enjin\/platform-/", $packageName));
    }

    public static function getPackageClass($package): string
    {
        $packageName = self::getPackageName($package);
        if (in_array($packageName, ['Core', 'Marketplace', 'Fuel-Tanks'])) {
            $packageClass = self::class;
        } else {
            $packageClass = "Enjin\\Platform\\{$packageName}\\Package";
        }

        return $packageClass;
    }

    public static function getPackageName($package): string
    {
        return Str::studly(Str::after($package, '-'));
    }
}
