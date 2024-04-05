<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances;

use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\Reserved as ReservedPolkadart;
use Enjin\Platform\Events\Substrate\Balances\Reserved as ReservedEvent;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Facades\Log;

class Reserved extends SubstrateEvent
{
    public function run(Event $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof ReservedPolkadart) {
            return;
        }

        $account = WalletService::firstOrStore(['account' => $event->who]);

        Log::info(sprintf(
            'Reserved %s in wallet %s (id: %s).',
            $event->amount,
            $account->address,
            $account->id,
        ));

        ReservedEvent::safeBroadcast(
            $account,
            $event->amount,
            $this->getTransaction($block, $event->extrinsicIndex),
        );
    }
}
