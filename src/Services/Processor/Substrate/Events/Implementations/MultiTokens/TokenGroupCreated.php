<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenGroupCreated as TokenGroupCreatedEvent;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\TokenGroup;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenGroupCreated as TokenGroupCreatedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class TokenGroupCreated extends SubstrateEvent
{
    /** @var TokenGroupCreatedPolkadart */
    protected Event $event;

    /**
     * @throws PlatformException
     */
    public function run(): void
    {
        if (!$this->shouldSyncCollection($this->event->collectionId)) {
            return;
        }

        $collection = $this->getCollection($this->event->collectionId);
        $this->extra = ['collection_owner' => $collection->owner->public_key];

        TokenGroup::firstOrCreate(
            [
                'collection_id' => $collection->id,
                'token_group_chain_id' => $this->event->tokenGroupId,
            ]
        );
    }

    public function log(): void
    {
        Log::debug(sprintf(
            'Token group %s was created in collection %s.',
            $this->event->tokenGroupId,
            $this->event->collectionId,
        ));
    }

    public function broadcast(): void
    {
        TokenGroupCreatedEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
