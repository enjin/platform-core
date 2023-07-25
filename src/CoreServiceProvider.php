<?php

namespace Enjin\Platform;

use Enjin\Platform\Commands\ClearCache;
use Enjin\Platform\Commands\Ingest;
use Enjin\Platform\Commands\Sync;
use Enjin\Platform\Commands\Transactions;
use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Events\Substrate\Commands\PlatformSynced;
use Enjin\Platform\Events\Substrate\Commands\PlatformSyncError;
use Enjin\Platform\Events\Substrate\Commands\PlatformSyncing;
use Enjin\Platform\Providers\AuthServiceProvider;
use Enjin\Platform\Providers\Deferred\BlockchainServiceProvider;
use Enjin\Platform\Providers\Deferred\QrServiceProvider;
use Enjin\Platform\Providers\Deferred\SerializationServiceProvider;
use Enjin\Platform\Providers\Deferred\WebsocketClientProvider;
use Enjin\Platform\Providers\FakerServiceProvider;
use Enjin\Platform\Providers\GraphQlServiceProvider;
use Enjin\Platform\Services\Processor\Substrate\BlockProcessor;
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
            ->hasConfigFile(['enjin-platform', 'graphql'])
            ->hasMigration('create_wallets_table')
            ->hasMigration('create_collections_table')
            ->hasMigration('create_collection_accounts_table')
            ->hasMigration('create_tokens_table')
            ->hasMigration('create_token_accounts_table')
            ->hasMigration('create_attributes_table')
            ->hasMigration('create_blocks_table')
            ->hasMigration('create_transactions_table')
            ->hasMigration('create_verifications_table')
            ->hasMigration('create_token_account_approvals_table')
            ->hasMigration('create_token_account_named_reserves_table')
            ->hasMigration('create_collection_account_approvals_table')
            ->hasMigration('create_collection_royalty_currencies_table')
            ->hasMigration('create_events_table')
            ->hasMigration('create_pending_events_table')
            ->hasMigration('add_signed_at_block')
            ->hasMigration('remove_linking_code_from_wallets_table')
            ->hasRoute('enjin-platform')
            ->hasCommand(Sync::class)
            ->hasCommand(Ingest::class)
            ->hasCommand(Transactions::class)
            ->hasCommand(ClearCache::class)
            ->hasTranslations();
    }

    /**
     * Bootstrap any application services.
     */
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

        Event::listen(PlatformSyncing::class, fn () => BlockProcessor::synching());
        Event::listen(PlatformSynced::class, fn () => BlockProcessor::synchingDone());
        Event::listen(PlatformSyncError::class, fn () => BlockProcessor::synchingDone());

        Builder::macro('cursorPaginateWithTotal', function ($order, $limit, $cache = true) {
            if ($cache) {
                $totalCount = (int) Cache::remember(
                    $this->toSql(),
                    6,
                    fn () => Cache::lock(PlatformCache::PAGINATION->key($this->toSql()))->get(fn () => $this->count())
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
                    $this->toSql(),
                    6,
                    fn () => Cache::lock(PlatformCache::PAGINATION->key($this->toSql()))->get(fn () => $this->count())
                );
            }

            return [
                'total' => $totalCount ?? $this->count(),
                'items' => $this->orderByDesc($order)->cursorPaginate($limit),
            ];
        });

        Collection::macro('recursive', function () {
            return $this->whenNotEmpty($recursive = function ($item) use (&$recursive) {
                if (is_array($item)) {
                    return $recursive(new static($item));
                } elseif ($item instanceof Collection) {
                    $item->transform(static function ($collection, $key) use ($recursive, $item) {
                        return $item->{$key} = $recursive($collection);
                    });
                } elseif (is_object($item)) {
                    foreach ($item as $key => &$val) {
                        $item->{$key} = $recursive($val);
                    }
                }

                return $item;
            });
        });
    }
}
