<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances;

use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\DustLost as DustLostPolkadart;
use Enjin\Platform\Events\Substrate\Balances\DustLost as DustLostEvent;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class DustLost extends SubstrateEvent
{
    public function run(Event $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof DustLostPolkadart) {
            return;
        }

        $account = $this->firstOrStoreAccount($event->account);

        Log::info(sprintf(
            'Wallet %s (id: %s) lost %s of dust.',
            $account->address,
            $account->id,
            $event->amount,
        ));

        DustLostEvent::safeBroadcast(
            $account,
            $event->amount,
            $this->getTransaction($block, $event->extrinsicIndex),
        );
    }
}
