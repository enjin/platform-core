<?php

namespace Enjin\Platform\Services;

use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Enums\Global\SettingsEnum;
use Enjin\Platform\Enums\TelemetrySource;
use Enjin\Platform\Http\Controllers\PlatformController;
use Illuminate\Support\Arr;
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
        if (!$this->isEnabled()) {
            return;
        }

        $packages = $this->platformPackages();

        try {
            $uuid = Cache::rememberForever(
                PlatformCache::TELEMETRY_UUID->value,
                fn () => $this->getPlatformIdentifier()
            );

            $os = php_uname('s');
            $machineType = php_uname('m');
            $phpVersion = PHP_VERSION;
            $platformVersion = Arr::get($packages, 'enjin/platform-core');

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
                    'packages' => $packages,
                    'source' => $source->value,
                ]);
        } catch (Throwable $e) {
            Log::error('Failed to send telemetry event.', ['message' => $e->getMessage()]);
        }
    }

    public function isEnabled(): bool
    {
        return config('telemetry.enabled') && config('telemetry.enabled') !== 'false';
    }

    protected function getPlatformIdentifier(): string
    {
        if (!$uuid = DB::table('settings')->where('key', SettingsEnum::TELEMETRY_UUID->value)->value('value')) {
            DB::table('settings')->insert([
                'key' => SettingsEnum::TELEMETRY_UUID->value,
                'value' => $uuid = Str::uuid()->toString(),
            ]);
        }

        return $uuid;
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
