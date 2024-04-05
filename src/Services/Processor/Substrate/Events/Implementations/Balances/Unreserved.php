<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Balances;

use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances\Unreserved as UnreservedPolkadart;
use Enjin\Platform\Events\Substrate\Balances\Unreserved as UnreservedEvent;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Facades\Log;

class Unreserved extends SubstrateEvent
{
    public function run(Event $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof UnreservedPolkadart) {
            return;
        }

        $account = WalletService::firstOrStore(['account' => $event->who]);

        Log::info(sprintf(
            'Reserved %s in wallet %s (id: %s).',
            $event->amount,
            $account->address,
            $account->id,
        ));

        UnreservedEvent::safeBroadcast(
            $account,
            $event->amount,
            $this->getTransaction($block, $event->extrinsicIndex),
        );
    }
}
