<?php

namespace Enjin\Platform\Services\Processor\Substrate;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Clients\Implementations\SubstrateWebsocket;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Enums\Substrate\StorageKey;
use Enjin\Platform\Enums\Substrate\SystemEventType;
use Enjin\Platform\Models\Event;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\System\ExtrinsicFailed;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\System\ExtrinsicSuccess;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;
use Enjin\Platform\Support\SS58Address;
use Facades\Enjin\Platform\Services\Database\TransactionService;
use Facades\Enjin\Platform\Services\Processor\Substrate\State;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExtrinsicProcessor
{
    protected Block $block;
    protected Codec $codec;

    public function __construct(Block $block, Codec $codec)
    {
        $this->block = $block;
        $this->codec = $codec;
    }

    public function run(): array
    {
        Log::info("Processing Extrinsics from block #{$this->block->number}");
        $extrinsics = $this->block->extrinsics ?? [];
        $errors = [];

        foreach ($extrinsics as $index => $extrinsic) {
            try {
                $this->processExtrinsic($extrinsic, $index);
            } catch (Throwable $exception) {
                $errors[] = sprintf('%s: %s (Line %s in %s)', get_class($exception), $exception->getMessage(), $exception->getLine(), $exception->getFile());
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
            'wallet_public_key' => SS58Address::getPublicKey($extrinsic->signer),
        ])->orderBy('created_at', 'desc')->first();

        if ($transaction) {
            if ($this->block->events === null) {
                Log::info('Fetching events for block #' . $this->block->number);
                $rpc = new SubstrateWebsocket();
                $blockHash = $this->block->hash;

                if ($blockHash === null) {
                    $blockHash = $rpc->send('chain_getBlockHash', [$this->block->number]);
                }

                if ($events = $rpc->send('state_getStorage', [StorageKey::EVENTS->value, $blockHash])) {
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
            ->map(function ($event) use ($transaction) {
                $params = $event->getParams();
                if ($event->name == 'FuelTankCreated') {
                    foreach ($params as &$param) {
                        $param['value'] = match ($param['type']) {
                            'tankName' => HexConverter::hexToString($param['value']),
                            default => $param['value']
                        };
                    }
                }

                return [
                    'transaction_id' => $transaction->id,
                    'phase' => '2',
                    'look_up' => 'unknown',
                    'module_id' => $event->module,
                    'event_id' => $event->name,
                    'params' => json_encode($params),
                ];
            })->toArray();

        Event::insert($eventsWithTransaction);
    }
}
