<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances;

use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\Transfer as TransferPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Events\Substrate\Balances\Transfer as TransferEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Facades\Log;

class Transfer extends SubstrateEvent
{
    public function run(Event $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof TransferPolkadart || is_null($event->extrinsicIndex)) {
            return;
        }

        $fromAccount = $this->firstOrStoreAccount($event->from);
        $toAccount = $this->firstOrStoreAccount($event->to);

        Log::info(sprintf(
            'Wallet %s (id: %s) has transferred %s to wallet %s (id: %s).',
            $fromAccount->address,
            $fromAccount->id,
            $event->amount,
            $toAccount->address,
            $toAccount->id,
        ));

        TransferEvent::safeBroadcast(
            $fromAccount,
            $toAccount,
            $event->amount,
            $this->getTransaction($block, $event->extrinsicIndex),
        );
    }
}
