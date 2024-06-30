<?php

namespace Enjin\Platform\Commands;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Commands\contexts\Truncate;
use Enjin\Platform\Enums\Global\ModelType;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Syncable;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Support\JSON;
use Enjin\Platform\Support\Util;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use JsonException;
use Random\RandomException;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;
use WebSocket\Client;
use WebSocket\Middleware\CloseHandler;
use WebSocket\Middleware\PingResponder;

class FastSync extends Command
{
    protected const PAGE_SIZE = 1000;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    public $signature = 'platform:fast-sync';

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

    //    protected ?string $lastKey = null;

    protected ?string $blockHash = null;
    protected ?string $block = null;
    protected ?array $pendingKeys = null;

    protected int $keysCount = 0;
    protected int $storageCount = 0;

    protected array $data = [];

    protected bool $finishedFetchingAllKeys = false;

    protected int $currentlyFetchingKey = 0;

    protected array $fetchKeys;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->description = __('enjin-platform::commands.sync.description');
        $this->nodeUrl = 'wss://archive.matrix.canary.enjin.io'; // networkConfig('node');
        $this->fetchKeys = $this->getKeys();
        $this->start = now();

    }

    /**
     * Process the command.
     *
     * @throws Exception
     */
    public function handle(): int
    {
        $this->info(__('enjin-platform::commands.sync.header'));
        if (!$this->truncateTables()) {
            throw new PlatformException(__('enjin-platform::error.failed_to_truncate'));
        }

        try {
            $client = new Client($this->nodeUrl);
            $client
                ->addMiddleware(new CloseHandler())
                ->addMiddleware(new PingResponder())
                ->onConnect(function ($client) {
                    echo "# Connected to {$this->nodeUrl}\n";

                    $this->sendGetBlockHash($client);

                })
                ->onClose(function ($client, $connection, $message) {
                    // Closed from outside
                    echo "> [{$connection->getRemoteName()}] Received [{$message->getOpcode()}] {$message->getCloseStatus()}\n";
                })
                ->onDisconnect(function ($client, $connection) {
                    echo "> [{$connection->getRemoteName()}] Server disconnected\n";
                })
                ->onError(function ($client, $connection, $exception) {
                    $name = $connection ? "[{$connection->getRemoteName()}]" : '[-]';
                    echo "> {$name} Error: {$exception->getMessage()}\n";
                })
                ->onText(
                    fn ($client, $connection, $message) => $this->handleLoop($client, $connection, $message)
                )
                ->onTick(function ($client) {
                    $step = $this->stepHandler($client);

                    switch ($step) {
                        case 'get_keys_paged':
                            $this->sendGetKeysPaged($client);

                            break;
                        case 'query_storage_at':
                            if (!$client->isWritable() || empty($this->pendingKeys)) {
                                return;
                            }

                            $keys = $this->pendingKeys;
                            $this->pendingKeys = [];

                            foreach ($keys as $key) {
                                $this->sendQueryStorageAt($client, $key);
                            }

                            break;
                        default:
                            $this->info($step);
                    }



                })->start();

        } catch (Throwable $e) {
            echo "# ERROR: {$e->getMessage()} [{$e->getCode()}]\n";
        }

        return CommandAlias::SUCCESS;
    }

    public function sendGetBlock($client, $blockHash): void
    {
        $client->text(Util::createJsonRpc('chain_getBlock', [
            $blockHash ?? null,
        ], $this->getId(1, 0)));
    }

    protected function stepHandler($client): string
    {
        if (is_null($this->blockHash)) {
            return 'get_block_hash';
        }

        if (is_null($this->block)) {
            return 'get_block';
        }

        if (is_null($this->pendingKeys)) {
            return 'get_keys_paged';
        }

        return 'query_storage_at';
    }

    protected function sendGetBlockHash($client): void
    {
        $client->text(Util::createJsonRpc(
            'chain_getBlockHash',
            [],
            $this->getId(0, 0)
        ));
    }

    /**
     * @throws RandomException
     * @throws JsonException
     */
    protected function sendQueryStorageAt($client, $keys = null): void
    {
        $client->text(Util::createJsonRpc('state_queryStorageAt', [
            $keys,
            $this->blockHash ?? null,
        ], $this->getId(3, $this->storageCount)));

        $this->storageCount++;
    }

    /**
     * @throws RandomException
     * @throws JsonException
     */
    protected function sendGetKeysPaged($client, $startKey = null): void
    {
        $client->text(Util::createJsonRpc('state_getKeysPaged', [
            $this->fetchKeys[$this->currentlyFetchingKey]->value,
            self::PAGE_SIZE,
            $startKey ?? null,
            $this->blockHash ?? null,
        ], $this->getId(2, $this->keysCount)));

        $this->keysCount++;
    }

    protected function parseBlockHash($client, $blockHash): void
    {
        $this->blockHash = $blockHash;
        $this->sendGetBlock($client, $blockHash);
    }

    protected function parseBlock($client, $result): void
    {
        $blockNumber = Arr::get($result, 'block.header.number');
        $blockNumber = HexConverter::hexToUInt($blockNumber);

        $this->block = Block::create([
            'number' => $blockNumber,
            'hash' => $this->blockHash,
            'synced' => false,
        ]);
    }

    protected function parseKeysPaged($client, $id, $result): void
    {
        $initialId = $id - (2 << 20);
        $lastKey = Arr::last($result);

        if ($lastKey === null) {
            $this->finishedFetchingAllKeys = true;

            return;
        }

        $this->pendingKeys[] = $result;
        $this->data[$this->currentlyFetchingKey][0] = $this->fetchKeys[$this->currentlyFetchingKey];
        $this->data[$this->currentlyFetchingKey][2] = isset($this->data[$this->currentlyFetchingKey][2]) ? $this->data[$this->currentlyFetchingKey][2] + count($result) : count($result);

        $this->sendGetKeysPaged($client, $lastKey);
    }

    protected function parseStorageAt($client, $id, $result): void
    {
        $initialId = $id - (3 << 20);
        $this->data[$this->currentlyFetchingKey][1][$initialId] = Arr::get($result, '0.changes');
    }

    protected function handleLoop($client, $connection, $message): void
    {
        $content = JSON::decode($message->getContent(), true);
        $category = $this->getCategory($id = Arr::get($content, 'id'));
        $result = Arr::get($content, 'result');

        switch ($category) {
            case 0:
                $this->parseBlockHash($client, $result);

                break;
            case 1:
                $this->parseBlock($client, $result);

                break;
            case 2:
                $this->parseKeysPaged($client, $id, $result);

                break;
            case 3:
                $this->parseStorageAt($client, $id, $result);

                if ($this->finishedFetchingAllKeys) {
                    ray('Keys: ' . $this->keysCount);
                    ray('Storage: ' . $this->storageCount);

                    if ($this->storageCount == $this->keysCount - 1) {

                        $this->currentlyFetchingKey++;

                        if ($this->currentlyFetchingKey == count($this->fetchKeys)) {
                            $client->close();
                            $this->parseStorages();
                            $this->displayOverview($this->data);

                            return;
                        }

                        $this->keysCount = 0;
                        $this->storageCount = 0;
                        $this->finishedFetchingAllKeys = false;

                        $this->info('Fetching key ' . $this->currentlyFetchingKey . ' of ' . count($this->fetchKeys));
                        $this->sendGetKeysPaged($client);
                    }
                }

                break;
            default:
                echo "Invalid id: {$category}\n";
        }
    }

    /**
     * Parse the storages.
     */
    protected function parseStorages(): void
    {
        $this->newLine();
        $this->info(__('enjin-platform::commands.sync.decoding'));

        for ($x = 0; $x < count($this->data); $x++) {
            [$storageKey, $storageValues] = $this->data[$x];

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

        //        $this->block->synced = true;
        //        $this->block->save();
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

    protected function createAndStartDebugBar(int $steps): ProgressBar
    {
        $progress = $this->output->createProgressBar($steps);
        $progress->setFormat('debug');
        $progress->start();

        return $progress;
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

    protected function getId($type, $id): string
    {
        return ($type << 20) + $id;
    }

    protected function getCategory($id): int
    {
        return $id >> 20;
    }
}
