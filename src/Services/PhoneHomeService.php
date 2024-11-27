<?php

namespace Enjin\Platform\Services;

use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Enums\TelemetrySource;
use Enjin\Platform\Http\Controllers\PlatformController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class PhoneHomeService
{
    public function phone(TelemetrySource $source = TelemetrySource::REQUEST): void
    {
        if (!config('telemetry.enabled') || config('telemetry.enabled') === 'false') {
            return;
        }

        try {
            $uuid = Cache::rememberForever(
                PlatformCache::TELEMETRY_UUID->value,
                function () {
                    if (!$uuid = DB::table('telemetry')->first()?->uuid) {
                        DB::table('telemetry')->insert(['uuid' => $uuid = Str::uuid()->toString()]);
                    }

                    return $uuid;
                }
            );

            $os = php_uname('s');
            $machineType = php_uname('m');
            $phpVersion = PHP_VERSION;
            $platformVersion = '0.1.2';

            $userAgent = "Enjin-Platform/{$platformVersion} ({$os} {$machineType}; PHP {$phpVersion})";
            Http::asJson()
                ->withHeaders(['User-Agent' => $userAgent])
                ->post('https://phoning.api.enjin.io', [
                    'uuid' => $uuid,
                    'product' => 'enjin-platform',
                    'version' => $platformVersion,
                    'email' => config('telemetry.email', ''),
                    'app' => [
                        'name' => config('app.name'),
                        'environment' => config('app.env'),
                        'host' => config('app.url'),
                        'network' => network()?->value,
                    ],
                    'os' => [
                        'info' => php_uname('a'),
                        'name' => $os,
                        'type' => $machineType,
                    ],
                    'php' => $phpVersion,
                    'packages' => $this->platformPackages(),
                    'source' => $source->value,
                ]);
            dd('sent');
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }
    }

    protected function platformPackages(): array
    {
        $packages = [];
        foreach (PlatformController::getPlatformPackages() as $package => $info) {
            $packages[$package] = $info['version'];
        }

        return $packages;
    }
}
