<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenGroupsUpdated as TokenGroupsUpdatedEvent;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\TokenGroup;
use Enjin\Platform\Models\Laravel\TokenGroupToken;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenGroupsUpdated as TokenGroupsUpdatedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class TokenGroupsUpdated extends SubstrateEvent
{
    /** @var TokenGroupsUpdatedPolkadart */
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

        TokenGroupToken::where('token_id', $token->id)->delete();

        $tokenGroups = TokenGroup::whereIn('token_group_chain_id', $this->event->tokenGroups)
            ->where('collection_id', $collection->id)
            ->get()
            ->keyBy('token_group_chain_id');

        $tokenGroupTokens = [];

        foreach ($this->event->tokenGroups as $index => $tokenGroupChainId) {
            $tokenGroup = $tokenGroups->get($tokenGroupChainId);

            if (!$tokenGroup) {
                continue;
            }

            $tokenGroupTokens[] = [
                'token_group_id' => $tokenGroup->id,
                'token_id' => $token->id,
                'position' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($tokenGroupTokens)) {
            TokenGroupToken::insert($tokenGroupTokens);
        }
    }

    public function log(): void
    {
        Log::debug(sprintf(
            'Token %s-%s token groups were updated.',
            $this->event->collectionId,
            $this->event->tokenId,
        ));
    }

    public function broadcast(): void
    {
        TokenGroupsUpdatedEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
