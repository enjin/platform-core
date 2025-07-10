<?php

namespace Enjin\Platform;

use Closure;
use Enjin\Platform\Commands\ClearCache;
use Enjin\Platform\Commands\RelayWatcher;
use Enjin\Platform\Commands\SendTelemetryEvent;
use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Http\Middleware\Telemetry;
use Enjin\Platform\Providers\AuthServiceProvider;
use Enjin\Platform\Providers\Deferred\BlockchainServiceProvider;
use Enjin\Platform\Providers\Deferred\QrServiceProvider;
use Enjin\Platform\Providers\Deferred\SerializationServiceProvider;
use Enjin\Platform\Providers\Deferred\WebsocketClientProvider;
use Enjin\Platform\Providers\FakerServiceProvider;
use Enjin\Platform\Providers\GitHubServiceProvider;
use Enjin\Platform\Providers\GraphQlServiceProvider;
use Enjin\Platform\Providers\GraphiQLRoutesServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Override;
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
            ->hasCommand(ClearCache::class)
            ->hasCommand(RelayWatcher::class)
            ->hasCommand(SendTelemetryEvent::class)
            ->hasTranslations();
    }

    /**
     * Bootstrap any application services.
     */
    #[Override]
    public function boot(): void
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
        $this->app->register(GraphiQLRoutesServiceProvider::class);
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

        Builder::macro('cursorPaginateWithTotal', function ($order, $limit, $cache = true, $desc = false) {
            $totalCount = null;
            if ($cache) {
                $totalCount = (int) Cache::remember(
                    $this->toRawSql(),
                    6,
                    fn () => Cache::lock(PlatformCache::PAGINATION->key($this->toRawSql()))->get(fn () => $this->count())
                );
            }

            $cursorPaginator = $desc
                ? $this->orderByDesc($order)->cursorPaginate($limit)
                : $this->orderBy($order)->cursorPaginate($limit);

            return [
                'cursorPaginator' => $cursorPaginator,
                'pageInfo' => [
                    'hasNextPage' => $cursorPaginator->hasMorePages(),
                    'hasPreviousPage' => !$cursorPaginator->onFirstPage(),
                    'startCursor' => $cursorPaginator->cursor()?->encode() ?? '',
                    'endCursor' => $cursorPaginator->nextCursor()?->encode() ?? '',
                ],
                'totalCount' => max($totalCount ?? $this->count(), count($cursorPaginator->items())),
            ];
        });

        Builder::macro('cursorPaginateWithTotalDesc', fn ($order, $limit, $cache = true) => $this->cursorPaginateWithTotal($order, $limit, $cache, true));

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

    #[Override]
    public function booted(Closure $callback): void
    {
        parent::booted($callback);
    }
}
