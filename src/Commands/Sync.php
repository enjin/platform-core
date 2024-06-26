<?php

namespace Enjin\Platform\Commands;

use Amp\Future;
use Amp\Serialization\SerializationException;
use Amp\Sync\ChannelException;
use Carbon\Carbon;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Clients\Implementations\SubstrateWebsocket;
use Enjin\Platform\Commands\contexts\Truncate;
use Enjin\Platform\Enums\Global\ModelType;
use Enjin\Platform\Events\Substrate\Commands\PlatformSynced;
use Enjin\Platform\Events\Substrate\Commands\PlatformSyncError;
use Enjin\Platform\Events\Substrate\Commands\PlatformSyncing;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Syncable;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use STS\Backoff\Backoff;
use STS\Backoff\Strategies\PolynomialStrategy;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;

use function Amp\async;
use function Amp\Parallel\Context\contextFactory;

class Sync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    public $signature = 'platform:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    public $description;

    /**
     * The blockchain node url.
     */
    protected string $nodeUrl;

    /**
     * The start time of the sync.
     */
    protected Carbon $start;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->description = __('enjin-platform::commands.sync.description');
        $this->nodeUrl = networkConfig('node');
        $this->start = now();
    }

    /**
     * Process the command.
     *
     * @throws Exception
     */
    public function handle(Backoff $backoff, SubstrateWebsocket $rpc): int
    {
        PlatformSyncing::dispatch();

        $backoff->setStrategy(new PolynomialStrategy(250, 2))
            ->setWaitCap(600000)
            ->setErrorHandler(function (?Throwable $e) {
                $this->error(__('enjin-platform::error.exception_in_sync'));
                $this->error($e->getMessage());
                $this->error($message = __('enjin-platform::error.line_and_file', ['line' => $e->getLine(), 'file' => $e->getFile()]));
                PlatformSyncError::dispatch($message);
            })
            ->run(fn () => $this->startSync($rpc));

        PlatformSynced::dispatch();

        return CommandAlias::SUCCESS;
    }

    /**
     * Start the sync process.
     *
     * @throws PlatformException
     */
    protected function startSync(SubstrateWebsocket $rpc): void
    {
        $this->info(__('enjin-platform::commands.sync.header'));
        if (!$this->truncateTables()) {
            throw new PlatformException(__('enjin-platform::error.failed_to_truncate'));
        }

        $block = $this->getCurrentBlock($rpc);
        $storages = $this->getStorageAt($block->hash);
        $this->parseStorages($block, $storages);
        $this->displayOverview($storages);
    }

    /**
     * Display the overview of the sync.
     */
    protected function displayOverview(array $storages): void
    {
        $this->info(__('enjin-platform::commands.sync.overview'));
        foreach ($storages as $storage) {
            $this->info(sprintf(
                '%s: %s',
                ucwords(str_replace('_', ' ', strtolower($storage[0]->type->name))),
                $storage[2]
            ));
        }
        $this->info(__('enjin-platform::commands.sync.total_time', ['sec' => now()->diffInMilliseconds($this->start) / 1000]));
        $this->info('=======================================================');
    }

    /**
     * Get the current block.
     */
    protected function getCurrentBlock(SubstrateWebsocket $rpc): Block
    {
        $blockHash = $rpc->send('chain_getBlockHash');
        $blockNumber = Arr::get($rpc->send('chain_getBlock', [$blockHash]), 'block.header.number');
        $rpc->close();

        if (!$blockHash || !$blockNumber) {
            throw new PlatformException(__('enjin-platform::error.failed_to_get_current_block'));
        }

        $blockNumber = HexConverter::hexToUInt($blockNumber);
        $this->info(__('enjin-platform::commands.sync.syncing', ['blockNumber' => $blockNumber]));

        return Block::create([
            'number' => $blockNumber,
            'hash' => $blockHash,
            'synced' => false,
        ]);
    }

    protected function createAndStartDebugBar(int $steps): ProgressBar
    {
        $progress = $this->output->createProgressBar($steps);
        $progress->setFormat('debug');
        $progress->start();

        return $progress;
    }

    /**
     * Get the storage at the given block hash.
     *
     * @throws PlatformException
     */
    protected function getStorageAt(string $blockHash): array
    {
        $this->info(__('enjin-platform::commands.sync.fetching'));
        $storageKeys = $this->getStorageKeys($blockHash);
        $progress = $this->createAndStartDebugBar(count($storageKeys));

        $storages = Future\await(
            array_map(
                fn ($keyAndHash) => async(function () use ($progress, $keyAndHash) {
                    try {
                        $storageKey = $keyAndHash[0];

                        $context = contextFactory()->start(__DIR__ . '/contexts/get_storage.php');
                        $context->send($keyAndHash);
                        [$storage, $total] = $context->join();

                        $this->newLine();
                        $this->info('Finished to fetch: ' . $storageKey->type->name . ' storage');

                        $progress->advance();

                        return [$storageKey, $storage, $total];
                    } catch (SerializationException|ChannelException $e) {
                        throw new PlatformException("Failed to sync: {$e->getMessage()}");
                    }
                }),
                $storageKeys,
            )
        );

        $progress->finish();

        return $storages;
    }

    protected function getKeys(): array
    {
        if (config('enjin-platform.sync.all')) {
            return Substrate::getStorageKeys();
        }

        $collectionFilter = Syncable::query()
            ->where('syncable_type', ModelType::COLLECTION)
            ->pluck('syncable_id');

        return Substrate::getStorageKeysForCollectionIds($collectionFilter);
    }

    protected function getStorageKeys(string $blockHash): array
    {
        $storage = $this->getKeys();

        if (class_exists($class = '\Enjin\Platform\FuelTanks\Enums\Substrate\StorageKey')) {
            $storage = array_merge($storage, [$class::tanks(), $class::accounts()]);
        }

        if (class_exists($class = '\Enjin\Platform\Marketplace\Enums\Substrate\StorageKey')) {
            $storage = array_merge($storage, [$class::listings()]);
        }

        return array_map(
            fn ($key) => [$key, $blockHash, $this->nodeUrl, $this->output],
            $storage
        );
    }

    /**
     * Parse the storages.
     */
    protected function parseStorages(Block $block, array $storages): void
    {
        $this->newLine();
        $this->info(__('enjin-platform::commands.sync.decoding'));

        for ($x = 0; $x < count($storages); $x++) {
            [$storageKey, $storageValues] = $storages[$x];

            $this->info('Parsing and saving ' . $storageKey->type->name);
            $progress = $this->createAndStartDebugBar(count($storageValues));

            foreach ($storageValues as $storagePage) {
                $facade = $storageKey->parserFacade();
                $facade::{$storageKey->parser()}($storagePage);
                $progress->advance();

            }

            $progress->finish();
            $this->newLine();
        }

        $block->synced = true;
        $block->save();
    }

    /**
     * Truncate the tables.
     */
    protected function truncateTables(): bool
    {
        $this->info(__('enjin-platform::commands.sync.truncating'));

        try {
            Schema::disableForeignKeyConstraints();
            array_map(
                fn ($table) => DB::table($table)->truncate(),
                $this->tablesToTruncate()
            );
        } catch (Exception $e) {
            Schema::enableForeignKeyConstraints();

            return false;
        }
        Schema::enableForeignKeyConstraints();

        return true;
    }

    protected function tablesToTruncate(): array
    {
        $tables = Truncate::tables();

        if (class_exists($truncate = '\Enjin\Platform\FuelTanks\Commands\contexts\Truncate')) {
            $tables = array_merge($tables, $truncate::tables());
        }

        if (class_exists($truncate = '\Enjin\Platform\Marketplace\Commands\contexts\Truncate')) {
            $tables = array_merge($tables, $truncate::tables());
        }

        return $tables;
    }
}
