<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances;

use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\Deposit as DepositPolkadart;
use Enjin\Platform\Events\Substrate\Balances\Deposit as DepositEvent;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class Deposit extends SubstrateEvent
{
    public function run(Event $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof DepositPolkadart) {
            return;
        }

        $account = $this->firstOrStoreAccount($event->who);

        Log::info(sprintf(
            'Wallet %s (id: %s) made a deposit of %s.',
            $account->address,
            $account->id,
            $event->amount,
        ));

        DepositEvent::safeBroadcast(
            $account,
            $event->amount,
            $this->getTransaction($block, $event->extrinsicIndex),
        );
    }
}
