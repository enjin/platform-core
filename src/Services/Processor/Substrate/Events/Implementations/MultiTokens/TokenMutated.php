<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenMutated as TokenMutatedEvent;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenMutated as TokenMutatedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Enjin\Platform\Support\Account;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Facades\Log;

class TokenMutated extends SubstrateEvent
{
    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof TokenMutatedPolkadart) {
            return;
        }

        ray($event);

        if (!$this->shouldIndexCollection($event->collectionId)) {
            return;
        }

        $collection = $this->getCollection($event->collectionId);
        $token = $this->getToken($collection->id, $event->tokenId);

        throw new \Exception('stop');
        $attributes = [];
        if ($listingForbidden = $event->listingForbidden) {
            $attributes['listing_forbidden'] = $listingForbidden;
        }

        if ($event->behaviorMutation === 'SomeMutation') {
            $attributes['is_currency'] = $event->isCurrency;
            $attributes['royalty_wallet_id'] = null;
            $attributes['royalty_percentage'] = null;

            if ($event->beneficiary) {
                $attributes['royalty_wallet_id'] = WalletService::firstOrStore(['account' => Account::parseAccount($event->beneficiary)])->id;
                $attributes['royalty_percentage'] = number_format($event->percentage / 1000000000, 9);
            }
        }

        $token->fill($attributes)->save();
        Log::info("Token #{$token->token_chain_id} (id {$token->id}) of Collection #{$collection->collection_chain_id} (id {$collection->id}) was updated.");

        $extrinsic = $block->extrinsics[$event->extrinsicIndex];

        TokenMutatedEvent::safeBroadcast(
            $token,
            $event->getParams(),
            Transaction::firstWhere(['transaction_chain_hash' => $extrinsic->hash])
        );
    }
}
