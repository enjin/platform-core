<?php

namespace Enjin\Platform\Services\Processor\Substrate;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Clients\Implementations\SubstrateWebsocket;
use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Enums\Substrate\StorageKey;
use Enjin\Platform\Events\Substrate\Commands\PlatformBlockIngested;
use Enjin\Platform\Events\Substrate\Commands\PlatformBlockIngesting;
use Enjin\Platform\Exceptions\RestartIngestException;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Support\JSON;
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

    public const SYNC_WAIT_DELAY = 5;

    protected Codec $codec;
    protected bool $hasCheckedSubBlocks = false;
    protected Substrate $persistedClient;

    public function __construct()
    {
        $this->input = new ArgvInput();
        $this->output = new BufferedConsoleOutput();
        $this->codec = new Codec();
        $this->persistedClient = new Substrate(new SubstrateWebsocket());
    }

    public function latestBlock(): int|null
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

    public function latestSyncedBlock(): int
    {
        return Block::where('synced', true)->max('number') ?? 0;
    }

    public function checkParentBlocks(string $heightHexed)
    {
        $this->warn('Making sure no blocks were left behind');
        $lastBlockSynced = $this->latestSyncedBlock();
        $blockBeforeSubscription = HexConverter::hexToUInt($heightHexed) - 1;
        $this->warn("Last block synced: {$lastBlockSynced}");
        $this->warn("Block before subscription: {$blockBeforeSubscription}");

        if ($blockBeforeSubscription > $lastBlockSynced) {
            $this->warn('Processing blocks left behind');
            $this->fetchPastHeads($lastBlockSynced, $blockBeforeSubscription);
            $this->warn('Finished processing blocks left behind');
        }

        $this->hasCheckedSubBlocks = true;
        $this->warn('Starting processing blocks from subscription');
    }

    public function getHashWhenBlockIsFinalized(int $blockNumber): string
    {
        while (true) {
            $blockHash = $this->persistedClient->callMethod('chain_getBlockHash', [$blockNumber]);
            if ($blockHash) {
                $this->persistedClient->getClient()->close();

                return $blockHash;
            }
            usleep(100000);
        }
    }

    public function subscribeToNewHeads(): void
    {
        $sub = new Substrate(new SubstrateWebsocket());
        $this->warn('Starting subscription to new heads');

        try {
            $sub->callMethod('chain_subscribeNewHeads');
            while (true) {
                if ($response = $sub->getClient()->receive()) {
                    $syncTime = now();
                    $result = Arr::get(JSON::decode($response, true), 'params.result');
                    $heightHexed = Arr::get($result, 'number');

                    if (null === $heightHexed) {
                        continue;
                    }

                    if (!$this->hasCheckedSubBlocks) {
                        $this->checkParentBlocks($heightHexed);
                    }

                    $blockNumber = HexConverter::hexToUInt($heightHexed);
                    $blockHash = $this->getHashWhenBlockIsFinalized($blockNumber);

                    $this->pauseWhenSynching();

                    $block = Block::updateOrCreate(
                        ['number' => $blockNumber],
                        ['hash' => $blockHash],
                    );

                    PlatformBlockIngesting::dispatch($block);

                    $this->info(sprintf('Ingested header for block #%s in %s seconds', $blockNumber, now()->diffInMilliseconds($syncTime) / 1000));

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

    public function ingest(): void
    {
        $currentHeight = $this->latestBlock();
        $lastBlockSynced = $this->latestSyncedBlock();

        $this->info('================ Starting Substrate Ingest ================');
        $this->info("Current block on-chain: {$currentHeight}");
        $this->info('Last ingested block: ' . $lastBlockSynced ?: 'No blocks ingested');
        $this->info('=========================================================');

        $this->startIngest($lastBlockSynced, $currentHeight);

        $this->info('An error has occurred the ingest process has been stopped.');
    }

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

    public function process(Block $block): Block|null
    {
        try {
            $blockNumber = $block->number;
            $syncTime = now();

            if ($block->synced) {
                $this->info("Block #{$blockNumber} already processed, skipping");

                return $block;
            }

            $this->info("Processing block #{$blockNumber} ({$block->hash})");

            (new EventProcessor($block, $this->codec))->run();
            (new ExtrinsicProcessor($block, $this->codec))->run();

            $block->fill(['synced' => true, 'failed' => false, 'exception' => null])->save();
            $this->info(sprintf("Process completed for block #{$blockNumber} in %s seconds", now()->diffInMilliseconds($syncTime) / 1000));
        } catch (Throwable $exception) {
            $this->error("Failed processing block #{$blockNumber}");
            $exception = sprintf('%s: %s (Line %s in %s)', get_class($exception), $exception->getMessage(), $exception->getLine(), $exception->getFile());
            $block->fill(['synced' => true, 'failed' => true, 'exception' => $exception])->save();
        }

        return $block;
    }

    /**
     * Check if synching is in progress.
     */
    public static function isSynching(): bool
    {
        return (bool) Cache::get(PlatformCache::SYNCING_IN_PROGRESS->key());
    }

    /**
     * Set flag to indicate synching is in progress.
     */
    public static function synching(): void
    {
        Cache::put(PlatformCache::SYNCING_IN_PROGRESS->key(), true);
    }

    /**
     * Remove flag to indicate synching is done.
     */
    public static function synchingDone(): void
    {
        Cache::forget(PlatformCache::SYNCING_IN_PROGRESS->key());
    }

    protected function startIngest(int $lastBlockSynced, int $currentHeight): void
    {
        try {
            $this->fetchPastHeads($lastBlockSynced, $currentHeight);
            $this->subscribeToNewHeads();
        } catch(RestartIngestException) {
            $this->startIngest(
                $this->latestSyncedBlock(),
                $this->latestBlock()
            );
        }
    }

    protected function fetchPreviousBlockHeads(int $blockNumber, int $blockLimit): void
    {
        while ($blockNumber <= $blockLimit) {
            $this->pauseWhenSynching();

            $syncTime = now();
            $block = Block::updateOrCreate(
                ['number' => $blockNumber],
                ['hash' => $this->persistedClient->callMethod('chain_getBlockHash', [$blockNumber])],
            );

            PlatformBlockIngesting::dispatch($block);

            $this->info(sprintf('Ingested header for block #%s in %s seconds', $block->number, now()->diffInMilliseconds($syncTime) / 1000));

            $this->fetchEvents($block);
            $this->fetchExtrinsics($block);
            $this->process($block);

            PlatformBlockIngested::dispatch($block);

            $blockNumber++;
        }

        $this->warn('Finished fetching past block heads');
    }

    protected function setBlockEvent(Substrate $blockchain, Block $block): Block
    {
        if ($events = $blockchain->callMethod('state_getStorage', [StorageKey::EVENTS->value, $block->hash])) {
            $block->events = State::eventsForBlock(['number' => $block->number, 'events' => $events]) ?? [];
        }

        return $block;
    }

    protected function setBlockExtrinsic(Substrate $blockchain, Block $block): Block
    {
        $data = $blockchain->callMethod('chain_getBlock', [$block->hash]);
        if ($extrinsics = Arr::get($data, 'block.extrinsics')) {
            $block->extrinsics = State::extrinsicsForBlock(['number' => $block->number, 'extrinsics' => json_encode($extrinsics)]) ?? [];
        }

        return $block;
    }

    protected function fetchEvents(Block $block): Block
    {
        $syncTime = now();
        $block = $this->setBlockEvent($this->persistedClient, $block);

        $this->info(sprintf('Ingested events for block #%s in %s seconds', $block->number, now()->diffInMilliseconds($syncTime) / 1000));

        return $block;
    }

    protected function fetchExtrinsics(Block $block): Block
    {
        $syncTime = now();
        $block = $this->setBlockExtrinsic($this->persistedClient, $block);

        $this->info(sprintf('Ingested extrinsics for block #%s in %s seconds', $block->number, now()->diffInMilliseconds($syncTime) / 1000));

        return $block;
    }

    /**
     * Pause the ingest process when the sync is running.
     */
    protected function pauseWhenSynching(): void
    {
        if (static::isSynching()) {
            $this->info('Pausing ingest, waiting for sync to complete...');
            $counter = 1;
            while (static::isSynching()) {
                sleep(static::SYNC_WAIT_DELAY);
                if ($counter * static::SYNC_WAIT_DELAY >= config('enjin-platform.sync_max_wait_timeout')) {
                    $this->warn('Sync has taken too long, forcing to restart ingest...');

                    $result = Process::pipe(function (Pipe $pipe) {
                        $pipe->command('ps aux');
                        $pipe->command('grep platform:sync');
                    });
                    if ($result->successful() && empty($result->output())) {
                        $this->warn('Sync is not running, updating flag to false...');
                        static::synchingDone();
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
