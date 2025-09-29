<?php

namespace Enjin\Platform\Services\Processor\Substrate;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Clients\Implementations\SubstrateSocketClient;
use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Enums\Substrate\StorageKey;
use Enjin\Platform\Events\Substrate\Commands\PlatformBlockIngested;
use Enjin\Platform\Events\Substrate\Commands\PlatformBlockIngesting;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Exceptions\RestartIngestException;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Support\JSON;
use Enjin\Platform\Support\Util;
use Exception;
use Facades\Enjin\Platform\Services\Processor\Substrate\State;
use Illuminate\Console\BufferedConsoleOutput;
use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Process\Pipe;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Console\Input\ArgvInput;
use Throwable;

class BlockProcessor
{
    use InteractsWithIO;

    public const int SYNC_WAIT_DELAY = 5;

    protected Codec $codec;
    protected bool $hasCheckedSubBlocks = false;
    protected Substrate $persistedClient;

    public function __construct()
    {
        $this->input = new ArgvInput();
        $this->output = new BufferedConsoleOutput();
        $this->codec = new Codec();
        $this->persistedClient = new Substrate(new SubstrateSocketClient());
    }

    public function latestBlock(): ?int
    {
        try {
            if ($currentBlock = $this->persistedClient->callMethod('chain_getHeader')) {
                return (int) HexConverter::hexToUInt($currentBlock['number']);
            }
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }

        return null;
    }

    public function lastSyncedBlock(): ?Block
    {
        return Block::where('synced', true)->orderByDesc('number')->first();
    }

    /**
     * @throws RestartIngestException
     */
    public function checkParentBlocks(string $heightHexed): void
    {
        $this->warn('Making sure no blocks were left behind');
        $lastSyncedHeight = $this->lastSyncedBlock()?->number ?? 0;
        $blockBeforeSubscription = HexConverter::hexToUInt($heightHexed) - 1;
        $this->warn("Last block synced: {$lastSyncedHeight}");
        $this->warn("Block before subscription: {$blockBeforeSubscription}");

        if ($blockBeforeSubscription > $lastSyncedHeight) {
            $this->warn('Processing blocks left behind');
            $this->fetchPastHeads($lastSyncedHeight, $blockBeforeSubscription);
            $this->warn('Finished processing blocks left behind');
        }

        $this->hasCheckedSubBlocks = true;
        $this->warn('Starting processing blocks from subscription');
    }

    public function getHashWhenBlockIsFinalized(int $blockNumber): string
    {
        while (true) {
            $blockHash = $this->persistedClient->callMethod('chain_getBlockHash', [$blockNumber]);
            if (is_string($blockHash) && str_starts_with($blockHash, '0x')) {
                return $blockHash;
            }
            usleep(100000);
        }
    }

    public function subscribeToNewHeads(): void
    {
        $sub = new Substrate(new SubstrateSocketClient());
        $this->warn('Starting subscription to new heads');

        try {
            $sub->callMethod('chain_subscribeFinalizedHeads');
            while (true) {
                if ($response = $sub->getClient()->receive()) {
                    $syncTime = now();
                    $result = Arr::get(JSON::decode($response, true), 'params.result');
                    $heightHexed = Arr::get($result, 'number');

                    if ($heightHexed === null) {
                        continue;
                    }

                    if (!$this->hasCheckedSubBlocks) {
                        $this->checkParentBlocks($heightHexed);
                    }

                    $blockNumber = HexConverter::hexToUInt($heightHexed);
                    $blockHash = $this->getHashWhenBlockIsFinalized($blockNumber);

                    $this->pauseWhenSyncing();

                    $block = Block::updateOrCreate(
                        ['number' => $blockNumber],
                        ['hash' => $blockHash],
                    );

                    PlatformBlockIngesting::dispatch($block);

                    $this->info(sprintf('Ingested header for block #%s in %s seconds', $blockNumber, $syncTime->diffInMilliseconds(now()) / 1000));

                    $this->fetchEvents($block);
                    $this->fetchExtrinsics($block);
                    $this->process($block);

                    PlatformBlockIngested::dispatch($block);
                }
            }
        } finally {
            $sub->getClient()->close();
        }
    }

    /**
     * @throws PlatformException
     */
    public function ingest(): void
    {
        Cache::forget(PlatformCache::CUSTOM_TYPES->key());

        $this->info('================ Starting Substrate Ingest ================');
        $this->info('Connected to: ' . currentMatrix()->value);

        $lastBlock = $this->latestBlock();
        $this->info("Current block on-chain: {$lastBlock}");

        $lastSyncedBlock = $this->lastSyncedBlock();
        $lastSyncedHeight = $lastSyncedBlock?->number ?? 0;
        $this->info("Continuing from block: {$lastSyncedHeight}");

        $runtime = Util::updateRuntimeVersion($lastSyncedBlock?->hash);
        $this->info("Transaction version: {$runtime[0]}");
        $this->info("Spec version: {$runtime[1]}");

        $this->info('=========================================================');
        $this->startIngest($lastSyncedHeight, $lastBlock);

        // Start ingest is a non-stopping process, so the following line will run only if it has crashed
        $this->error('An error has occurred the ingest process has been stopped.');
    }

    /**
     * @throws RestartIngestException
     */
    public function fetchPastHeads(int $startingHeight, int $currentHeight): void
    {
        $numOfBlocks = $currentHeight - $startingHeight;
        if ($numOfBlocks <= 0) {
            return;
        }

        $startBlock = $startingHeight + 1;
        $this->fetchPreviousBlockHeads($startBlock, $currentHeight);

        $newCurrentHeight = $this->latestBlock();
        $this->warn("Current block on-chain: {$newCurrentHeight} - Last block processed: {$currentHeight}");
        $this->fetchPastHeads($currentHeight, $newCurrentHeight);
    }

    public function process(Block $block): ?Block
    {
        try {
            $blockNumber = $block->number;
            $syncTime = now();

            if ($block->synced) {
                $this->info("Block #{$blockNumber} already processed, skipping");

                return $block;
            }

            $this->info("Processing block #{$blockNumber} ({$block->hash})");

            $hasEventErrors = (new EventProcessor($block, $this->codec))->run();
            $hasExtrinsicErrors = (new ExtrinsicProcessor($block, $this->codec))->run();
            if ($hasEventErrors || $hasExtrinsicErrors) {
                $errors = implode(';', [...$hasEventErrors, ...$hasExtrinsicErrors]);

                throw new Exception($errors);
            }

            $block->fill(['synced' => true, 'failed' => false, 'exception' => null])->save();
            $this->info(sprintf("Process completed for block #{$blockNumber} in %s seconds", $syncTime->diffInMilliseconds(now()) / 1000));
        } catch (Throwable $exception) {
            $this->error("Failed processing block #{$blockNumber}: {$exception->getMessage()}");
            $block->fill(['synced' => true, 'failed' => true, 'exception' => $exception->getMessage()])->save();
        }

        return $block;
    }

    /**
     * Check if syncing is in progress.
     */
    public static function isSyncing(): bool
    {
        return (bool) Cache::get(PlatformCache::SYNCING_IN_PROGRESS->key());
    }

    /**
     * Set flag to indicate syncing is in progress.
     */
    public static function syncing(): void
    {
        Cache::put(PlatformCache::SYNCING_IN_PROGRESS->key(), true);
    }

    /**
     * Remove flag to indicate syncing is done.
     */
    public static function syncingDone(): void
    {
        Cache::forget(PlatformCache::SYNCING_IN_PROGRESS->key());
    }

    protected function startIngest(int $lastBlockSynced, int $currentHeight): void
    {
        $this->fetchPastHeads($lastBlockSynced, $currentHeight);
        $this->subscribeToNewHeads();
    }

    /**
     * @throws RestartIngestException
     */
    protected function fetchPreviousBlockHeads(int $blockNumber, int $blockLimit): void
    {
        while ($blockNumber <= $blockLimit) {
            $this->pauseWhenSyncing();

            $syncTime = now();
            $block = Block::updateOrCreate(
                ['number' => $blockNumber],
                ['hash' => $this->persistedClient->callMethod('chain_getBlockHash', [$blockNumber])],
            );

            PlatformBlockIngesting::dispatch($block);

            $this->info(sprintf('Ingested header for block #%s in %s seconds', $block->number, $syncTime->diffInMilliseconds(now()) / 1000));

            $this->fetchEvents($block);
            $this->fetchExtrinsics($block);
            $this->process($block);

            PlatformBlockIngested::dispatch($block);

            $blockNumber++;
        }

        $this->warn('Finished fetching past block heads');
    }

    protected function runOrWaitIfEmpty($f, $action, $blockNumber): mixed
    {
        $try = 0;

        while (empty($result = call_user_func($f)) && $try < 3) {
            usleep($sleep = 1000000 * $try ** 2);
            $this->warn(sprintf('Retrying to fetch %s for block #%s in %s seconds', $action, $blockNumber, $sleep / 1000000));
            $try++;
        }

        return $result;
    }

    protected function fetchEvents(Block $block): Block
    {
        $syncTime = now();

        $data = $this->runOrWaitIfEmpty(
            fn () => $this->persistedClient->callMethod('state_getStorage', [StorageKey::events()->value, $block->hash]),
            'events',
            $block->number
        );

        if (empty($data)) {
            $this->warn('No events found for block #' . $block->number);

            return $block;
        }

        $block->events = $this->runOrWaitIfEmpty(
            fn () => State::eventsForBlock(['number' => $block->number, 'events' => $data]),
            'events',
            $block->number
        );

        $this->info(sprintf('Ingested events for block #%s in %s seconds', $block->number, $syncTime->diffInMilliseconds(now()) / 1000));

        return $block;
    }

    protected function fetchExtrinsics(Block $block): Block
    {
        $syncTime = now();

        $data = $this->runOrWaitIfEmpty(
            fn () => $this->persistedClient->callMethod('chain_getBlock', [$block->hash]),
            'extrinsics',
            $block->number
        );

        if (empty($extrinsics = Arr::get($data, 'block.extrinsics'))) {
            return $block;
        }

        $block->extrinsics = $this->runOrWaitIfEmpty(
            fn () => State::extrinsicsForBlock(['number' => $block->number, 'extrinsics' => json_encode($extrinsics)]),
            'blocks',
            $block->number
        );

        $this->info(sprintf('Ingested extrinsics for block #%s in %s seconds', $block->number, $syncTime->diffInMilliseconds(now()) / 1000));

        return $block;
    }

    /**
     * Pause the ingest process when the sync is running.
     */
    protected function pauseWhenSyncing(): void
    {
        if (static::isSyncing()) {
            $this->info('Pausing ingest, waiting for sync to complete...');
            $counter = 1;
            while (static::isSyncing()) {
                sleep(static::SYNC_WAIT_DELAY);
                if ($counter * static::SYNC_WAIT_DELAY >= config('enjin-platform.sync_max_wait_timeout')) {
                    $this->warn('Sync has taken too long, forcing to restart ingest...');

                    $result = Process::pipe(function (Pipe $pipe): void {
                        $pipe->command('ps aux');
                        $pipe->command('grep platform:sync');
                    });
                    if ($result->successful() && empty($result->output())) {
                        $this->warn('Sync is not running, updating flag to false...');
                        static::syncingDone();
                    }

                    break;
                }
                $counter++;
            }
            $this->info('Sync completed, restarting ingest...');

            throw new RestartIngestException();
        }
    }
}
