<?php

namespace Enjin\Platform\Commands;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Enums\Substrate\StorageKey;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Laravel\Transaction;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\ExtrinsicProcessor;
use Facades\Enjin\Platform\Services\Processor\Substrate\State;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

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

    /**
     * Execute the job.
     */
    public function handle(Substrate $client, Codec $codec): void
    {
        $this->codec = $codec;
        $this->client = $client;

        $start = now();

        $blockNumber = Block::where('synced', true)->max('number');
        $this->info("Going until block {$blockNumber}");

        // 25 blocos atras = 5min
        // 50 blocks = 10min
//        $lastBlock = max(0, $blockNumber - 100000);
//        ray('When start searching');


        $transactions = Transaction::where([
            'state' => TransactionState::BROADCAST,
        ])->whereNotNull(['signed_at_block', 'transaction_chain_hash'])
//            ->whereBetween('signed_at_block', [$lastBlock, $blockNumber])
            ->get();
//        ray($transactions);

        $counter = $transactions->count();

        if ($transactions->isEmpty()) {
            return;
        }

        $minSignedAtBlock = $transactions->min('signed_at_block');
        $this->info("Starting on block {$minSignedAtBlock}");

        if ($minSignedAtBlock > $blockNumber) {
            $this->info("No transactions to check");
            return;
        }

        ray($minSignedAtBlock);

        $hashes = array_filter($transactions->pluck('transaction_chain_hash')->toArray());
        ray($hashes);

        for ($i = $minSignedAtBlock; $i <= $blockNumber; $i++) {
            ray($i);
            $block = Block::firstWhere('number', $i);
            if (!($block?->hash)) {
                $block = Block::updateOrCreate(
                    ['number' => $i],
                    ['hash' => $client->callMethod('chain_getBlockHash', [$i])],
                );
            }

            $extrinsics = $this->fetchExtrinsics($block, $client);
//            ray($extrinsics);
            $hashesFromThisBlock = collect($extrinsics)->pluck('hash')->toArray();

            if (($i - $minSignedAtBlock) > 500) {
                $this->info("Did not find transaction signed at block $minSignedAtBlock in the last 500 blocks");

                $transactions = collect($transactions)->filter(fn ($transaction) => $transaction->signed_at_block != $minSignedAtBlock);
                $minSignedAtBlock = collect($transactions)->min('signed_at_block');

                if ($minSignedAtBlock <= $i) {
                    $this->info("Continuing trying to find transaction signed at block $minSignedAtBlock");
                } else {
                  $this->info("Skipping from block: {$i} to block: {$minSignedAtBlock}");
                  $i = $minSignedAtBlock - 1;
                }
            }

            if (count(array_intersect($hashes, $hashesFromThisBlock)) > 0) {
                    // Found extrinsic
                    $block->events = $this->fetchEvents($block, $client);
                    $block->extrinsics = $extrinsics;
//                    ray($block);
                    $hasExtrinsicErrors = (new ExtrinsicProcessor($block, $this->codec))->run();
                    if (!empty($hasExtrinsicErrors)) {
                        ray($hasExtrinsicErrors);
                    }
//                    ray($hasExtrinsicErrors);

//                    ray("Removing hash for hashes array");
                    $hashes = array_diff($hashes, $hashesFromThisBlock);
                    $transactions = collect($transactions)->filter(fn ($transaction) => !in_array($transaction->transaction_chain_hash, $hashesFromThisBlock));
                    $minSignedAtBlock = collect($transactions)->min('signed_at_block');

                    if ($minSignedAtBlock > $i) {
                        $this->info(sprintf("Skipping from block: {$i} to block: %s", $minSignedAtBlock));
                        $i = $minSignedAtBlock - 1;
                    }
            }

            if (empty($hashes)) {
                break;
            }

//            ray($extrinsics);
        }

        $end = collect($transactions)->count();
        $counter = $counter - $end;

        $this->info(sprintf("We could not find the following transactions: %s", json_encode($hashes)));
        $this->info("Fixed {$counter} transactions");

        $this->info(__('enjin-platform::commands.sync.total_time', ['sec' => now()->diffInMilliseconds($start) / 1000]));
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
        if ($events = $client->callMethod('state_getStorage', [StorageKey::EVENTS->value, $block->hash])) {
            return State::eventsForBlock(['number' => $block->number, 'events' => $events]) ?? [];
        }

        return [];
    }

}
