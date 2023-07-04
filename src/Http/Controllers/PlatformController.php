<?php

namespace Enjin\Platform\Http\Controllers;

use Composer\InstalledVersions;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class PlatformController extends Controller
{
    /**
     * Get platform information.
     */
    public function getPlatformInfo(): JsonResponse
    {
        $installedPackages = collect(InstalledVersions::getInstalledPackages())
            ->filter(fn ($packageName) => preg_match("/^enjin\/platform-/", $packageName));

        $packages = $installedPackages->mapWithKeys(function ($package) {
            $packageName = Str::studly(Str::afterLast($package, '-'));
            if ($packageName == 'Core') {
                $packageClass = 'Enjin\\Platform\\Package';
            } else {
                $packageClass = 'Enjin\\Platform\\' . Str::studly(Str::afterLast($package, '-')) . '\\Package';
            }

            $info = [
                'version' => InstalledVersions::getVersion($package),
                'revision' => InstalledVersions::getReference($package),
            ];

            if (class_exists($packageClass) && !empty($routes = $packageClass::getPackageRoutes())) {
                $info['routes'] = $routes;
            }

            return [$package => $info];
        });

        $platformData = [
            'root' => 'enjin/platform-core',
            'url' => trim(config('app.url'), '/'),
            'chain' => config('enjin-platform.chains.selected'),
            'network' => config('enjin-platform.chains.network'),
            'packages' => $packages,
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
