<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenGroupAttributeRemoved as TokenGroupAttributeRemovedEvent;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Attribute;
use Enjin\Platform\Models\Laravel\TokenGroup;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenGroupAttributeRemoved as TokenGroupAttributeRemovedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class TokenGroupAttributeRemoved extends SubstrateEvent
{
    /** @var TokenGroupAttributeRemovedPolkadart */
    protected Event $event;

    /**
     * @throws PlatformException
     */
    public function run(): void
    {
        $tokenGroup = TokenGroup::with('collection.owner')->firstWhere('token_group_chain_id', $this->event->tokenGroupId);

        if (!$tokenGroup) {
            return;
        }

        $this->extra = ['collection_owner' => $tokenGroup->collection->owner->public_key];

        if (!$this->shouldSyncCollection($tokenGroup->collection->collection_chain_id)) {
            return;
        }

        Attribute::where([
            'token_group_id' => $tokenGroup->id,
            'key' => HexConverter::prefix($this->event->key),
        ])->delete();
    }

    public function log(): void
    {
        Log::debug(sprintf(
            'Attribute "%s" of token group %s was removed.',
            $this->event->key,
            $this->event->tokenGroupId,
        ));
    }

    public function broadcast(): void
    {
        TokenGroupAttributeRemovedEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
