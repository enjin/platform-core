<?php

namespace Enjin\Platform\Jobs;

use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Laravel\Transaction;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\ExtrinsicProcessor;
use Facades\Enjin\Platform\Services\Processor\Substrate\State;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;

class TransactionChecker implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected Codec $codec;
    protected Substrate $client;

    /**
     * Execute the job.
     */
    public function handle(Substrate $client, Codec $codec): void
    {
        $this->codec = $codec;
        $this->client = $client;


        // Verificar todas transacoes que estao paradas em broadcast
        // Essas transacoes nao podem ser mais velhas que Y minutos
        // Essas transacoes nao podem ser mais novas do que o bloco atual do ingest

        // Pegar essas transacoes e ver o signedAtBlock
        // Fazer query da chain a partir desse signedAtBlock e ver se a transacao foi confirmada

        $blockNumber = Block::where('synced', true)->max('number');

        // 25 blocos atras = 5min
        // 50 blocks = 10min
        $lastBlock = max(0, $blockNumber - 50);


        $transactions = Transaction::where([
            'state' => TransactionState::BROADCAST,
        ])
//            ->whereBetween('signed_at_block', [$lastBlock, $blockNumber])
            ->get();
        ray($transactions);

        if ($transactions->isEmpty()) {
            return;
        }

        $hashes = $transactions->pluck('transaction_chain_hash')->toArray();


        for ($i = $lastBlock; $i <= $blockNumber; $i++) {
            $block = Block::firstWhere('number', $i);
            $extrinsics = $this->fetchExtrinsics($block, $client);

            foreach ($extrinsics as $extrinsic) {
                if (in_array($extrinsic['hash'], $hashes)) {
                    // Found extrinsic
                    $hasExtrinsicErrors = (new ExtrinsicProcessor($block, $this->codec))->run();
                    ray($extrinsic);
                }
            }

            ray($extrinsics);
        }



//        if ($data = $this->data) {
//            try {
//                DB::beginTransaction();
//
//
//
//
//                DB::commit();
//            } catch (Throwable $e) {
//                DB::rollBack();
//
//                throw $e;
//            }
//        }
    }

    protected function fetchExtrinsics($block, Substrate $client): mixed
    {
        $data = $client->callMethod('chain_getBlock', [$block->hash]);
        if ($extrinsics = Arr::get($data, 'block.extrinsics')) {
            return State::extrinsicsForBlock(['number' => $block->number, 'extrinsics' => json_encode($extrinsics)]) ?? [];
        }

        return [];
    }

    protected function
}
