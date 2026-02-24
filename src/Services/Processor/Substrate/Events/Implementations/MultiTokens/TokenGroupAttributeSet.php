<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Events\Substrate\MultiTokens\TokenGroupAttributeSet as TokenGroupAttributeSetEvent;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Attribute;
use Enjin\Platform\Models\Laravel\TokenGroup;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenGroupAttributeSet as TokenGroupAttributeSetPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class TokenGroupAttributeSet extends SubstrateEvent
{
    /** @var TokenGroupAttributeSetPolkadart */
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

        Attribute::updateOrCreate(
            [
                'token_group_id' => $tokenGroup->id,
                'key' => HexConverter::prefix($this->event->key),
            ],
            [
                'collection_id' => $tokenGroup->collection_id,
                'value' => HexConverter::prefix($this->event->value),
            ]
        );
    }

    public function log(): void
    {
        Log::debug(sprintf(
            'Attribute "%s" of token group %s was set to "%s".',
            $this->event->key,
            $this->event->tokenGroupId,
            $this->event->value,
        ));
    }

    public function broadcast(): void
    {
        TokenGroupAttributeSetEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
