<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenGroupDestroyed as TokenGroupDestroyedEvent;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\TokenGroup;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\TokenGroupDestroyed as TokenGroupDestroyedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class TokenGroupDestroyed extends SubstrateEvent
{
    /** @var TokenGroupDestroyedPolkadart */
    protected Event $event;

    /**
     * @throws PlatformException
     */
    public function run(): void
    {
        $tokenGroup = TokenGroup::firstWhere('token_group_chain_id', $this->event->tokenGroupId);

        if (!$tokenGroup) {
            return;
        }

        $tokenGroup->delete();
    }

    public function log(): void
    {
        Log::debug(sprintf(
            'Token group %s was destroyed.',
            $this->event->tokenGroupId,
        ));
    }

    public function broadcast(): void
    {
        TokenGroupDestroyedEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }
}
