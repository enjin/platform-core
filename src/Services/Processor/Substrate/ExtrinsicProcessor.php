<?php

namespace Enjin\Platform\Services\Processor\Substrate;

use Enjin\Platform\Clients\Implementations\SubstrateSocketClient;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Enums\Substrate\StorageType;
use Enjin\Platform\Enums\Substrate\SystemEventType;
use Enjin\Platform\Models\Event;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\System\ExtrinsicFailed;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\System\ExtrinsicSuccess;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;
use Facades\Enjin\Platform\Services\Database\TransactionService;
use Facades\Enjin\Platform\Services\Processor\Substrate\State;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExtrinsicProcessor
{
    public function __construct(protected Block $block, protected Codec $codec) {}

    public function run(): array
    {
        Log::info("Processing Extrinsics from block #{$this->block->number}");
        $extrinsics = $this->block->extrinsics ?? [];
        $errors = [];

        foreach ($extrinsics as $index => $extrinsic) {
            try {
                $this->processExtrinsic($extrinsic, $index);
            } catch (Throwable $exception) {
                $errors[] = sprintf('%s: %s (Line %s in %s)', $exception::class, $exception->getMessage(), $exception->getLine(), $exception->getFile());
            }
        }

        return $errors;
    }

    protected function processExtrinsic(PolkadartExtrinsic $extrinsic, int $index)
    {
        if (empty($extrinsic->signer)) {
            return;
        }

        $transaction = Transaction::where([
            'transaction_chain_hash' => $extrinsic->hash,
        ])->orderBy('created_at', 'desc')->first();

        if ($transaction) {
            if ($this->block->events === null) {
                Log::info('Fetching events for block #' . $this->block->number);
                $rpc = new SubstrateSocketClient();
                $blockHash = $this->block->hash;

                if ($blockHash === null) {
                    $blockHash = $rpc->send('chain_getBlockHash', [$this->block->number]);
                }

                if ($events = $rpc->send('state_getStorage', [StorageType::EVENTS->value, $blockHash])) {
                    $this->block->events = State::eventsForBlock(['number' => $this->block->number, 'events' => $events]) ?? [];
                }
            }

            $this->updateTransaction($transaction, $index);
            $this->saveExtrinsicEvents($transaction, $index);
            Log::info(
                sprintf(
                    'Updated transaction %s with extrinsic id: %s',
                    $transaction->transaction_chain_hash,
                    $transaction->transaction_chain_id,
                )
            );
        }
    }

    protected function updateTransaction($transaction, int $index): void
    {
        $extrinsicId = "{$this->block->number}-{$index}";
        $resultEvent = collect($this->block->events)->firstWhere(
            fn ($event) => (($event instanceof ExtrinsicSuccess) || ($event instanceof ExtrinsicFailed))
                && $event->extrinsicIndex == $index
        );

        TransactionService::update($transaction, [
            'transaction_chain_id' => $extrinsicId,
            'state' => TransactionState::FINALIZED->name,
            'result' => SystemEventType::tryFrom(class_basename($resultEvent))?->name,
        ]);
    }

    protected function saveExtrinsicEvents($transaction, int $index): void
    {
        Event::where('transaction_id', $transaction->id)->delete();

        $eventsWithTransaction = collect($this->block->events)->filter(fn ($event) => $event->extrinsicIndex == $index)
            ->map(fn ($event) => [
                'transaction_id' => $transaction->id,
                'phase' => '2',
                'look_up' => 'unknown',
                'module_id' => $event->module,
                'event_id' => $event->name,
                'params' => json_encode($event->getParams()),
            ])->toArray();

        Event::insert($eventsWithTransaction);
    }
}
