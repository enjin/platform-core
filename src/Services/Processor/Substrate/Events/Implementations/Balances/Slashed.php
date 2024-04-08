<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances;

use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\Slashed as SlashedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Events\Substrate\Balances\Slashed as SlashedEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class Slashed extends SubstrateEvent
{
    public function run(Event $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof SlashedPolkadart) {
            return;
        }

        $account = $this->firstOrStoreAccount($event->who);

        Log::info(sprintf(
            'Wallet %s (id: %s) was slashed %s.',
            $account->address,
            $account->id,
            $event->amount,
        ));

        SlashedEvent::safeBroadcast(
            $account,
            $event->amount,
            $this->getTransaction($block, $event->extrinsicIndex),
        );
    }
}
