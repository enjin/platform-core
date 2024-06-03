<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\CollectionAttributeSet;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenAttributeSet;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Attribute;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\AttributeSet as AttributeSetPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class AttributeSet extends SubstrateEvent
{
    /** @var AttributeSetPolkadart */
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

        $attribute = Attribute::updateOrCreate(
            [
                'collection_id' => $collection->id,
                'token_id' => $token?->id,
                'key' => $this->event->key,
            ],
            [
                'value' => $this->event->value,
            ]
        );

        if ($attribute->wasRecentlyCreated) {
            is_null($this->event->tokenId)
                ? $collection->increment('attribute_count')
                : $token->increment('attribute_count');
        }
    }

    public function log(): void
    {
        Log::debug(
            sprintf(
                'Attribute "%s" of Collection %s%s was set to "%s".',
                $this->event->key,
                $this->event->collectionId,
                is_null($this->event->tokenId) ? '' : sprintf(', Token %s ', $this->event->tokenId),
                $this->event->value,
            )
        );
    }

    public function broadcast(): void
    {
        if (is_null($this->event->tokenId)) {
            CollectionAttributeSet::safeBroadcast(
                $this->event,
                $this->getTransaction($this->block, $this->event->extrinsicIndex),
                $this->extra,
            );

            return;
        }

        TokenAttributeSet::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
