<?php

namespace Enjin\Platform\Commands;

use Carbon\Carbon;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Enums\Substrate\StorageType;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Laravel\Transaction;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\ExtrinsicProcessor;
use Facades\Enjin\Platform\Services\Processor\Substrate\State;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Helper\ProgressBar;

class TransactionChecker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    public $signature = 'platform:transaction-checker';

    /**
     * The console command description.
     *
     * @var string
     */
    public $description;

    protected Codec $codec;
    protected Substrate $client;
    protected ProgressBar $progressBar;
    protected Carbon $start;

    public function __construct()
    {
        parent::__construct();

        $this->description = __('enjin-platform::commands.transactions.description');
        $this->start = now();
    }

    /**
     * Execute the job.
     */
    public function handle(Substrate $client, Codec $codec): void
    {
        $this->codec = $codec;
        $this->client = $client;

        $syncedBlocks = Block::where('synced', true)
            ->orderBy('number')
            ->take(100)
            ->get();

        $maxBlockToCheck = $syncedBlocks->last()->number;

        $transactions = collect(Transaction::whereIn('state', [TransactionState::BROADCAST, TransactionState::EXECUTED])
            ->where('network', currentMatrix()->name)
            ->whereNotNull(['signed_at_block', 'transaction_chain_hash'])
            // We only check transactions older than 100 blocks because ingest
            // should be parsing and checking newer transactions
            ->where('signed_at_block', '<', $maxBlockToCheck)->get());

        if ($transactions->isEmpty()) {
            $this->info('There are no transactions to check.');

            return;
        }

        $minSignedAtBlock = $transactions->min('signed_at_block');
        $this->info(__('enjin-platform::commands.transactions.header'));
        $this->info(__('enjin-platform::commands.transactions.syncing', ['fromBlock' => $minSignedAtBlock, 'toBlock' => $maxBlockToCheck]));

        if ($minSignedAtBlock > $maxBlockToCheck) {
            $this->info('There are no transactions to check in those blocks');

            return;
        }

        $this->info(__('enjin-platform::commands.transactions.fetching'));
        $counter = $transactions->count();
        $hashes = array_filter($transactions->pluck('transaction_chain_hash')->toArray());

        $this->progressBar = $this->output->createProgressBar($counter);
        $this->progressBar->setFormat('debug');
        $this->progressBar->start();

        for ($i = $minSignedAtBlock; $i <= $maxBlockToCheck; $i++) {
            $this->progressBar->setProgress($counter - collect($transactions)->count());

            $block = Block::firstWhere('number', $i);
            if (!($block?->hash)) {
                $block = Block::updateOrCreate(
                    ['number' => $i],
                    ['hash' => $client->callMethod('chain_getBlockHash', [$i])],
                );
            }

            $extrinsics = $this->fetchExtrinsics($block, $client);
            $hashesFromThisBlock = collect($extrinsics)->pluck('hash')->toArray();

            if (($i - $minSignedAtBlock) > 300) {
                $this->displayMessageAboveBar("Did not find transaction signed at block {$minSignedAtBlock} in the last 300 blocks");

                $transactions = collect($transactions)->filter(fn ($transaction) => $transaction->signed_at_block != $minSignedAtBlock);
                $minSignedAtBlock = collect($transactions)->min('signed_at_block');

                if (empty($minSignedAtBlock) || $minSignedAtBlock >= $maxBlockToCheck) {
                    $this->displayMessageAboveBar('There are no more transactions to search for.');

                    break;
                }

                if ($minSignedAtBlock <= $i) {
                    $this->displayMessageAboveBar("Continuing trying to find transaction signed at block {$minSignedAtBlock}");
                } else {
                    $this->displayMessageAboveBar("Skipping from block {$i} to block {$minSignedAtBlock}");
                    $i = $minSignedAtBlock - 1;
                }
            }

            if (count(array_intersect($hashes, $hashesFromThisBlock)) > 0) {
                $block->events = $this->fetchEvents($block, $client);
                $block->extrinsics = $extrinsics;

                $hasExtrinsicErrors = (new ExtrinsicProcessor($block, $this->codec))->run();
                if (!empty($hasExtrinsicErrors)) {
                    $this->error(json_encode($hasExtrinsicErrors));
                }

                $this->displayMessageAboveBar(sprintf('Took %s blocks to find the transaction signed at block %s', $i - $minSignedAtBlock, $minSignedAtBlock));
                $hashes = array_diff($hashes, $hashesFromThisBlock);
                $transactions = collect($transactions)->filter(fn ($transaction) => !in_array($transaction->transaction_chain_hash, $hashesFromThisBlock));
                $minSignedAtBlock = collect($transactions)->min('signed_at_block');

                if ($minSignedAtBlock > $i) {
                    $this->displayMessageAboveBar(sprintf("Skipping from block {$i} to block %s", $minSignedAtBlock));
                    $i = $minSignedAtBlock - 1;
                }
            }

            if (empty($hashes)) {
                break;
            }
        }

        if (!empty($hashes)) {
            $this->info(sprintf('Could not find %d transactions in the searched blocks. They will be rechecked on the next run.', count($hashes)));
        }
        $this->displayOverview($counter, $hashes);
    }

    protected function setAbandonedState($hashes): void
    {
        Transaction::whereIn('transaction_chain_hash', $hashes)
            ->whereIn('state', [TransactionState::BROADCAST, TransactionState::EXECUTED])
            ->where('network', currentMatrix()->name)
            ->update([
                'state' => TransactionState::ABANDONED,
            ]);

    }

    protected function fetchExtrinsics($block, Substrate $client): mixed
    {
        $data = $client->callMethod('chain_getBlock', [$block->hash]);
        if ($extrinsics = Arr::get($data, 'block.extrinsics')) {
            return State::extrinsicsForBlock(['number' => $block->number, 'extrinsics' => json_encode($extrinsics)]) ?? [];
        }

        return [];
    }

    protected function fetchEvents($block, Substrate $client): mixed
    {
        if ($events = $client->callMethod('state_getStorage', [StorageType::EVENTS->value, $block->hash])) {
            return State::eventsForBlock(['number' => $block->number, 'events' => $events]) ?? [];
        }

        return [];
    }

    protected function displayOverview(int $counter, array $hashes): void
    {
        $this->progressBar->finish();
        $this->newLine();

        $this->info(__('enjin-platform::commands.transactions.overview'));
        $this->info(sprintf('We did not find the following transactions: %s', json_encode($hashes)));
        $this->info(sprintf('The command has fixed %s transactions.', $counter - collect($hashes)->count()));
        $this->info(sprintf('Command run for the total of %s seconds.', now()->diffInMilliseconds($this->start) / 1000));
        $this->info('=======================================================');
    }

    protected function displayMessageAboveBar(string $message): void
    {
        $this->progressBar->clear();
        $this->info($message);
        $this->progressBar->display();
    }
}
