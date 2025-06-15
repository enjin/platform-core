<?php

namespace Enjin\Platform;

use Closure;
use Enjin\Platform\Commands\ClearCache;
use Enjin\Platform\Commands\Ingest;
use Enjin\Platform\Commands\RelayWatcher;
use Enjin\Platform\Commands\SendTelemetryEvent;
use Enjin\Platform\Commands\Sync;
use Enjin\Platform\Commands\SyncMetadata;
use Enjin\Platform\Commands\TransactionChecker;
use Enjin\Platform\Commands\Transactions;
use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Events\Substrate\Commands\PlatformSynced;
use Enjin\Platform\Events\Substrate\Commands\PlatformSyncError;
use Enjin\Platform\Events\Substrate\Commands\PlatformSyncing;
use Enjin\Platform\Http\Middleware\Telemetry;
use Enjin\Platform\Providers\AuthServiceProvider;
use Enjin\Platform\Providers\Deferred\BlockchainServiceProvider;
use Enjin\Platform\Providers\Deferred\QrServiceProvider;
use Enjin\Platform\Providers\Deferred\SerializationServiceProvider;
use Enjin\Platform\Providers\Deferred\WebsocketClientProvider;
use Enjin\Platform\Providers\FakerServiceProvider;
use Enjin\Platform\Providers\GitHubServiceProvider;
use Enjin\Platform\Providers\GraphQlServiceProvider;
use Enjin\Platform\Services\Processor\Substrate\BlockProcessor;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CoreServiceProvider extends PackageServiceProvider
{
    /**
     * Configure the package.
     */
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('enjin-platform')
            ->hasConfigFile(['enjin-platform', 'enjin-runtime', 'graphql', 'telemetry'])
            ->discoversMigrations()
            ->hasRoute('enjin-platform')
            ->hasCommand(Sync::class)
            ->hasCommand(Ingest::class)
            ->hasCommand(Transactions::class)
            ->hasCommand(ClearCache::class)
            ->hasCommand(TransactionChecker::class)
            ->hasCommand(RelayWatcher::class)
            ->hasCommand(SyncMetadata::class)
            ->hasCommand(SendTelemetryEvent::class)
            ->hasTranslations();
    }

    /**
     * Bootstrap any application services.
     */
    #[\Override]
    public function boot()
    {
        parent::boot();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/enjin-platform.php');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'enjin-platform');

        $this->app->register(QrServiceProvider::class);
        $this->app->register(SerializationServiceProvider::class);
        $this->app->register(BlockchainServiceProvider::class);
        $this->app->register(WebsocketClientProvider::class);
        $this->app->register(GraphQlServiceProvider::class);
        $this->app->register(FakerServiceProvider::class);
        $this->app->register(AuthServiceProvider::class);
        $this->app->register(GitHubServiceProvider::class);

        $this->app[Kernel::class]->pushMiddleware(Telemetry::class);
        $this->app['config']->set('database.connections.indexer', [
            'driver' => config('enjin-platform.indexer.driver'),
            'url' => config('enjin-platform.indexer.url'),
            'host' => config('enjin-platform.indexer.host'),
            'port' => config('enjin-platform.indexer.port'),
            'database' => config('enjin-platform.indexer.database'),
            'username' => config('enjin-platform.indexer.username'),
            'password' => config('enjin-platform.indexer.password'),
            'charset' => config('enjin-platform.indexer.charset'),
            'prefix' => config('enjin-platform.indexer.prefix'),
            'prefix_indexes' => config('enjin-platform.indexer.prefix_indexes'),
            'search_path' => config('enjin-platform.indexer.search_path'),
            'sslmode' => config('enjin-platform.indexer.sslmode'),
        ]);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('platform:send-telemetry-event')->daily();
        });

        Event::listen(PlatformSyncing::class, fn () => BlockProcessor::syncing());
        Event::listen(PlatformSynced::class, fn () => BlockProcessor::syncingDone());
        Event::listen(PlatformSyncError::class, fn () => BlockProcessor::syncingDone());

        Builder::macro('cursorPaginateWithTotal', function ($order, $limit, $cache = true) {
            if ($cache) {
                $totalCount = (int) Cache::remember(
                    $this->toRawSql(),
                    6,
                    fn () => Cache::lock(PlatformCache::PAGINATION->key($this->toRawSql()))->get(fn () => $this->count())
                );
            }

            return [
                'total' => $totalCount ?? $this->count(),
                'items' => $this->orderBy($order)->cursorPaginate($limit),
            ];
        });

        Builder::macro('cursorPaginateWithTotalDesc', function ($order, $limit, $cache = true) {
            if ($cache) {
                $totalCount = (int) Cache::remember(
                    $this->toRawSql(),
                    6,
                    fn () => Cache::lock(PlatformCache::PAGINATION->key($this->toRawSql()))->get(fn () => $this->count())
                );
            }

            return [
                'total' => $totalCount ?? $this->count(),
                'items' => $this->orderByDesc($order)->cursorPaginate($limit),
            ];
        });

        Collection::macro('recursive', fn () => $this->whenNotEmpty($recursive = function ($item) use (&$recursive) {
            if (is_array($item)) {
                return $recursive(new static($item));
            } elseif ($item instanceof Collection) {
                $item->transform(static fn ($collection, $key) => $item->{$key} = $recursive($collection));
            } elseif (is_object($item)) {
                foreach ($item as $key => &$val) {
                    $item->{$key} = $recursive($val);
                }
            }

            return $item;
        }));
    }

    #[\Override]
    public function booted(Closure $callback)
    {
        parent::booted($callback);
    }
}
