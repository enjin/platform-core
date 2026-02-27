<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenGroupRemoved as TokenGroupRemovedEvent;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\TokenGroup;
use Enjin\Platform\Models\Laravel\TokenGroupToken;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenGroupRemoved as TokenGroupRemovedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class TokenGroupRemoved extends SubstrateEvent
{
    /** @var TokenGroupRemovedPolkadart */
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

        $token = $this->getToken($collection->id, $this->event->tokenId);

        $tokenGroup = TokenGroup::firstWhere([
            'collection_id' => $collection->id,
            'token_group_chain_id' => $this->event->tokenGroupId,
        ]);

        if (!$tokenGroup) {
            return;
        }

        TokenGroupToken::where([
            'token_group_id' => $tokenGroup->id,
            'token_id' => $token->id,
        ])->delete();
    }

    public function log(): void
    {
        Log::debug(sprintf(
            'Token %s-%s was removed from token group %s.',
            $this->event->collectionId,
            $this->event->tokenId,
            $this->event->tokenGroupId,
        ));
    }

    public function broadcast(): void
    {
        TokenGroupRemovedEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
