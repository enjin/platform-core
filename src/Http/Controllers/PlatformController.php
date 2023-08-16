<?php

namespace Enjin\Platform\Http\Controllers;

use Composer\InstalledVersions;
use Enjin\Platform\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class PlatformController extends Controller
{
    /**
     * Get platform packages.
     */
    public static function getPlatformPackages(): array
    {
        $installedPackages = Package::getInstalledPlatformPackages();

        return $installedPackages->mapWithKeys(function ($package) {
            $packageClass = Package::getPackageClass($package);

            $info = [
                'version' => InstalledVersions::getVersion($package),
                'revision' => InstalledVersions::getReference($package),
            ];

            if (class_exists($packageClass) && !empty($routes = $packageClass::getPackageRoutes())) {
                $info['routes'] = $routes;
            }

            return [$package => $info];
        })->all();
    }

    /**
     * Get platform information.
     */
    public function getPlatformInfo(): JsonResponse
    {
        $platformData = [
            'root' => 'enjin/platform-core',
            'url' => trim(config('app.url'), '/'),
            'chain' => config('enjin-platform.chains.selected'),
            'network' => config('enjin-platform.chains.network'),
            'packages' => static::getPlatformPackages(),
        ];

        return response()
            ->json($platformData, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            ->setCache([
                'public' => true,
                'max_age' => 10,
                's_maxage' => 60,
            ]);
    }
}
