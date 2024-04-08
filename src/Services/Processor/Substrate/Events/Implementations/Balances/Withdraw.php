<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances;

use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\Withdraw as WithdrawPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Events\Substrate\Balances\Withdraw as WithdrawEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class Withdraw extends SubstrateEvent
{
    public function run(Event $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof WithdrawPolkadart) {
            return;
        }

        $account = $this->firstOrStoreAccount($event->who);

        Log::info(sprintf(
            'Withdraw %s from wallet %s (id: %s).',
            $event->amount,
            $account->address,
            $account->id,
        ));

        WithdrawEvent::safeBroadcast(
            $account,
            $event->amount,
            $this->getTransaction($block, $event->extrinsicIndex),
        );
    }
}
