<?php

namespace Enjin\Platform\Commands;

use Amp\Future;
use Amp\Parallel\Context\ContextException;
use Amp\Parallel\Context\ProcessContextFactory;
use Amp\Serialization\SerializationException;
use Amp\Sync\ChannelException;
use Carbon\Carbon;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Clients\Implementations\SubstrateSocketClient;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\ExtrinsicProcessor;
use Facades\Enjin\Platform\Services\Processor\Substrate\State;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Symfony\Component\Console\Helper\ProgressBar;

use function Amp\async;

class Transactions extends Command
{
    public const int CONCURRENT_REQUESTS = 20;

    public $signature = 'platform:transactions {--from=} {--to=}';

    public $description;

    protected string $nodeUrl;

    protected ProgressBar $progressBar;

    protected Carbon $start;

    public function __construct()
    {
        parent::__construct();

        $this->description = __('enjin-platform::commands.transactions.description');
        $this->nodeUrl = config(sprintf('enjin-platform.chains.supported.substrate.%s.node', config('enjin-platform.chains.network')));
        $this->start = now();
    }

    /**
     * @throws PlatformException
     */
    public function handle(SubstrateSocketClient $rpc): int
    {
        $fromBlock = $this->option('from');
        if (!$fromBlock) {
            $this->warn(__('enjin-platform::commands.transactions.specify_start'));

            return CommandAlias::FAILURE;
        }

        $toBlock = $this->option('to');

        if (!$toBlock) {
            $blockHash = $rpc->send('chain_getBlockHash');
            $blockNumber = Arr::get($rpc->send('chain_getBlock', [$blockHash]), 'block.header.number');
            $rpc->close();

            if (!$blockHash || !$blockNumber) {
                throw new PlatformException(__('enjin-platform::error.failed_to_get_current_block'));
            }

            $toBlock = HexConverter::hexToUInt($blockNumber);
        }

        if ($toBlock < $fromBlock) {
            $this->warn(__('enjin-platform::commands.transactions.start_lower_than_end'));

            return CommandAlias::FAILURE;
        }

        $this->updateTransactions($fromBlock, $toBlock);

        return CommandAlias::SUCCESS;
    }

    protected function updateTransactions(int $fromBlock, int $toBlock): void
    {
        $this->info(__('enjin-platform::commands.transactions.header'));
        $this->info(__('enjin-platform::commands.transactions.syncing', ['fromBlock' => $fromBlock, 'toBlock' => $toBlock]));
        $this->info(__('enjin-platform::commands.transactions.fetching'));

        $rangeEnd = min($fromBlock + self::CONCURRENT_REQUESTS - 1, $toBlock);
        $requests = array_map(fn ($from) => [$this->nodeUrl, self::CONCURRENT_REQUESTS, $from, $toBlock], range($fromBlock, $rangeEnd));

        $totalBlocks = $toBlock - $fromBlock + 1;
        $totalProgress = $totalBlocks + count($requests);
        $this->progressBar = $this->output->createProgressBar($totalProgress);
        $this->progressBar->setFormat('debug');
        $this->progressBar->start();

        $extrinsics = Future\await(array_map(
            fn ($request) => async(/**
             * @throws ContextException
             * @throws SerializationException
             * @throws ChannelException
             */ function () use ($request) {
                $context = (new ProcessContextFactory())->start(__DIR__ . '/contexts/get_extrinsics.php');
                $context->send($request);
                $result = $context->join();
                $this->progressBar->advance();

                return $result;
            }),
            $requests
        ));

        $this->parseTransactions($extrinsics, $totalBlocks);
    }

    protected function parseTransactions(array $extrinsics, int $totalBlocks): void
    {
        $this->displayMessageAboveBar(__('enjin-platform::commands.transactions.decoding'));

        $codec = new Codec();
        $totalExtrinsics = 0;
        collect($extrinsics)->each(function ($chunk) use ($codec, &$totalExtrinsics): void {
            foreach ($chunk as $blockNumber => $extrinsics) {
                $block = new Block();
                $block->number = $blockNumber;
                $block->extrinsics = State::extrinsicsForBlock(['number' => $block->number, 'extrinsics' => json_encode($extrinsics)]) ?? [];
                $totalExtrinsics += count($block->extrinsics);

                (new ExtrinsicProcessor($block, $codec))->run();
                $this->progressBar->advance();
            }
        });

        $this->displayOverview($totalBlocks, $totalExtrinsics);
    }

    protected function displayOverview(int $totalBlocks, int $totalExtrinsics): void
    {
        $this->progressBar->finish();
        $this->newLine();

        $this->info(__('enjin-platform::commands.transactions.overview'));
        $this->info(__('enjin-platform::commands.transactions.total_extrinsics', ['extrinsics' => $totalExtrinsics]));
        $this->info(__('enjin-platform::commands.transactions.total_blocks', ['blocks' => $totalBlocks]));
        $this->info(__('enjin-platform::commands.transactions.total_time', ['sec' => now()->diffInMilliseconds($this->start) / 1000]));
        $this->info('=======================================================');
    }

    protected function displayMessageAboveBar(string $message): void
    {
        $this->progressBar->clear();
        $this->info($message);
        $this->progressBar->display();
    }
}
