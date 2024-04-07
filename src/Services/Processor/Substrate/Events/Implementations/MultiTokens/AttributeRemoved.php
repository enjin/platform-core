<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Events\Substrate\MultiTokens\CollectionAttributeRemoved;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenAttributeRemoved;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\AttributeRemoved as AttributeRemovedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class AttributeRemoved extends SubstrateEvent
{
    /**
     * @throws PlatformException
     */
    public function run(Event $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof AttributeRemovedPolkadart) {
            return;
        }

        if (!$this->shouldIndexCollection($event->collectionId)) {
            return;
        }

        // Fails if it doesn't find the collection
        $collection = $this->getCollection($event->collectionId);
        $token = !is_null($tokenId = $event->tokenId)
                // Fails if it doesn't find the token
                ? $this->getToken($collection->id, $tokenId)
                : null;

        // Fails if it doesn't find the attribute
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
                $event->collectionId,
                $collection->id,
                !is_null($tokenId) ? sprintf(' and Token #%s (id %s) ', $tokenId, $token->id) : ''
            )
        );

        $transaction = $this->getTransaction($block, $event->extrinsicIndex);

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
