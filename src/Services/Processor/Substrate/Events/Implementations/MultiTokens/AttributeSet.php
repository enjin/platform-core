<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Events\Substrate\MultiTokens\CollectionAttributeSet;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenAttributeSet;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Attribute;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\AttributeSet as AttributeSetPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Enjin\Platform\Support\Hex;
use Illuminate\Support\Facades\Log;

class AttributeSet extends SubstrateEvent
{
    /**
     * @throws PlatformException
     */
    public function run(Event $event, Block $block, Codec $codec): void
    {
        if (!$event instanceof AttributeSetPolkadart) {
            return;
        }

        if (!$this->shouldSyncCollection($event->collectionId)) {
            return;
        }

        // Fails if it doesn't find the collection
        $collection = $this->getCollection($event->collectionId);
        $token = !is_null($tokenId = $event->tokenId)
            // Fails if it doesn't find the token
            ? $this->getToken($collection->id, $tokenId)
            : null;


        $attribute = Attribute::updateOrCreate(
            [
                'collection_id' => $collection->id,
                'token_id' => $token?->id,
                'key' => HexConverter::prefix($this->event->key),
            ],
            [
                'value' => HexConverter::prefix($this->event->value),
            ]
        );

        Log::info(
            sprintf(
                'Attribute "%s" (id %s) of Collection #%s (id %s) %s was set to "%s".',
                $key,
                $attribute->id,
                $event->collectionId,
                $collection->id,
                !is_null($tokenId) ? sprintf('and Token #%s (id %s) ', $tokenId, $token->id) : '',
                $value,
            )
        );

        if ($attribute->wasRecentlyCreated) {
            $transaction = $this->getTransaction($block, $event->extrinsicIndex);

            if ($token) {
                $token->increment('attribute_count');
                TokenAttributeSet::safeBroadcast(
                    $token,
                    $key,
                    $value,
                    $transaction
                );
            } else {
                $collection->increment('attribute_count');
                CollectionAttributeSet::safeBroadcast(
                    $collection,
                    $key,
                    $value,
                    $transaction
                );
            }
        }
    }
}
