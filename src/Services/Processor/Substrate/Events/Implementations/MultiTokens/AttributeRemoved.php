<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Events\Substrate\MultiTokens\CollectionAttributeRemoved;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenAttributeRemoved;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\AttributeRemoved as AttributeRemovedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Traits;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class AttributeRemoved implements SubstrateEvent
{
    use Traits\QueryDataOrFail;

    public function run(PolkadartEvent $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof AttributeRemovedPolkadart) {
            return;
        }

        if (!$this->shouldIndexCollection($event->collectionId)) {
            return;
        }

        $collection = $this->getCollection(
            $collectionId = $event->collectionId,
        );

        $token = !is_null($tokenId = $event->tokenId)
                ? $this->getToken($collection->id, $tokenId)
                : null;

        $attribute = $this->getAttribute(
            $collection->id,
            $token?->id,
            $key = HexConverter::hexToString($event->key)
        );
        $attribute->delete();

        Log::info(
            sprintf(
                'Attribute "%s" (id %s) of Collection #%s (id %s) %s was removed.',
                $key,
                $attribute->id,
                $collectionId,
                $collection->id,
                !is_null($tokenId) ? sprintf(' and Token #%s (id %s) ', $tokenId, $token->id) : ''
            )
        );

        $extrinsic = $block->extrinsics[$event->extrinsicIndex];
        $transaction = Transaction::firstWhere(['transaction_chain_hash' => $extrinsic->hash]);

        if ($token) {
            $token->decrement('attribute_count');
            TokenAttributeRemoved::safeBroadcast(
                $token,
                $attribute->key,
                $attribute->value,
                $transaction
            );
        } else {
            $collection->decrement('attribute_count');
            CollectionAttributeRemoved::safeBroadcast(
                $collection,
                $attribute->key,
                $attribute->value,
                $transaction
            );
        }
    }
}
