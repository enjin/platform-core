<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenDestroyed as TokenDestroyedEvent;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenDestroyed as TokenDestroyedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Traits;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Facades\Log;

class TokenDestroyed implements SubstrateEvent
{
    use Traits\QueryDataOrFail;

    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof TokenDestroyedPolkadart) {
            return;
        }

        $collection = $this->getCollection($collectionId = $event->collectionId);
        $token = $this->getToken($collection->id, $tokenId = $event->tokenId);
        $token->delete();

        Log::info("Token #{$tokenId} in Collection ID {$collectionId} was destroyed.");

        $extrinsic = $block->extrinsics[$event->extrinsicIndex];

        TokenDestroyedEvent::safeBroadcast(
            $token,
            WalletService::firstOrStore(['account' => $event->caller]),
            Transaction::firstWhere(['transaction_chain_hash' => $extrinsic->hash])
        );
    }
}
