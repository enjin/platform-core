<?php

namespace Enjin\Platform\Http\Controllers;

use Composer\InstalledVersions;
use Enjin\Platform\Enums\Global\NetworkType;
use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class PlatformController extends Controller
{
    /**
     * Get platform packages.
     */
    public static function getPlatformPackages(): array
    {
        $installedPackages = Package::getInstalledPlatformPackages();
        ray($installedPackages);

        return $installedPackages->mapWithKeys(function ($package) {
            ray($package);
            $packageClass = Package::getPackageClass($package);
            ray($packageClass);

            $info = [
                'version' => InstalledVersions::getPrettyVersion($package),
                'revision' => InstalledVersions::getReference($package),
            ];

            if (class_exists($packageClass) && !empty($routes = $packageClass::getPackageRoutes())) {
                $info['routes'] = $routes;
            }

            return [$package => $info];
        })->all();
    }

    public static function getPlatformReleaseDiff(): JsonResponse
    {
        return response()
            ->json(static::getReleaseDiffData(), 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            ->setCache([
                'public' => true,
                'max_age' => 3600,
                's_maxage' => 3600,
            ]);
    }

    /**
     * Get platform information.
     */
    public function getPlatformInfo(): JsonResponse
    {
        $platformData = [
            'root' => 'enjin/platform-core',
            'url' => trim((string) config('app.url'), '/'),
            'chain' => chain()->value,
            'network' => network() === NetworkType::ENJIN_MATRIX ? 'enjin' : 'canary',
            'packages' => static::getPlatformPackages(),
            'release-diff' => static::getReleaseDiffData(true),
            'next-release' => static::getReleaseDiffData(),
        ];

        ray($platformData);

        return response()
            ->json($platformData, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            ->setCache([
                'public' => true,
                'max_age' => 10,
                's_maxage' => 60,
            ]);
    }

    protected static function getReleaseDiffData(bool $useInstalledRevision = false): array
    {
        return Cache::remember(PlatformCache::RELEASE_DIFF->key($useInstalledRevision ? 'current' : ''), now()->addHour(), function () use ($useInstalledRevision) {
            $installedPackages = Package::getInstalledPlatformPackages();
            $githubHttp = app('github.http');

            return $installedPackages->mapWithKeys(function ($package) use ($githubHttp, $useInstalledRevision) {
                try {
                    $masterSha = $githubHttp->get("repos/{$package}/commits/master");
                    $releaseTags = $githubHttp->get("repos/{$package}/tags");
                    if ($masterSha->ok() && $releaseTags->ok()) {
                        $masterSha = $useInstalledRevision ? InstalledVersions::getReference($package) : $masterSha->json()['sha'];
                        $releaseSha = $releaseTags->json()[0]['commit']['sha'];

                        $compare = $useInstalledRevision ? "{$masterSha}...{$releaseSha}" : "{$releaseSha}...{$masterSha}";
                        $response = $githubHttp->get("repos/{$package}/compare/{$compare}");
                        if ($response->ok()) {
                            $commits = collect(json_decode((string) $response->getBody()->getContents(), true)['commits']);

                            return [$package => $commits->map(fn ($commit) => $commit['commit']['message'])->reverse()->flatten()->all()];
                        }
                    }

                    return [];
                } catch (Throwable $exception) {
                    Log::error('There was an issue receiving data from the GitHub API: ' . $exception->getMessage());

                    return [];
                }
            })->all();
        });
    }
}
