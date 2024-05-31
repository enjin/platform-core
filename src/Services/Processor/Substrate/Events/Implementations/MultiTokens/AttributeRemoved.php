<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\CollectionAttributeRemoved;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenAttributeRemoved;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Attribute;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\AttributeRemoved as AttributeRemovedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class AttributeRemoved extends SubstrateEvent
{
    /** @var AttributeRemovedPolkadart */
    protected Event $event;

    /**
     * @throws PlatformException
     */
    public function run(): void
    {
        if (!$this->shouldSyncCollection($this->event->collectionId)) {
            return;
        }

        // Fails if it doesn't find the collection
        $collection = $this->getCollection($this->event->collectionId);
        $this->extra = ['collection_owner' => $collection->owner->public_key];

        $token = !is_null($tokenId = $this->event->tokenId)
                // Fails if it doesn't find the token
                ? $this->getToken($collection->id, $tokenId)
                : null;

        Attribute::where([
            'collection_id' =>  $collection->id,
            'token_id' => $token?->id,
            'key' => $this->event->key,
        ])->delete();

        is_null($this->event->tokenId)
            ? $collection->decrement('attribute_count')
            : $token->decrement('attribute_count');
    }

    public function log(): void
    {
        Log::info(
            sprintf(
                'Removed attribute %s from Collection %s%s',
                $this->event->key,
                $this->event->collectionId,
                is_null($this->event->tokenId) ? '.' : sprintf(', Token %s.', $this->event->tokenId)
            )
        );
    }

    public function broadcast(): void
    {
        if (is_null($this->event->tokenId)) {
            CollectionAttributeRemoved::safeBroadcast(
                $this->event,
                $this->getTransaction($this->block, $this->event->extrinsicIndex),
                $this->extra,
            );

            return;
        }

        TokenAttributeRemoved::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
