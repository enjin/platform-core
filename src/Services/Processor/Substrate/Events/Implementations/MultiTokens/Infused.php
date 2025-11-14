<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens;

use Enjin\Platform\Events\Substrate\MultiTokens\TokenInfused;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens\Infused as InfusedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Log;

class Infused extends SubstrateEvent
{
    /** @var InfusedPolkadart */
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
        // Fails if it doesn't find the token
        $token = $this->getToken($collection->id, $this->event->tokenId);

        $collection->update(['total_infusion' => $this->increaseInfusedValue($collection->total_infusion, $this->event->amount)]);
        $token->update(['infusion' => $this->increaseInfusedValue($token->infusion, $this->event->amount)]);
    }

    public function log(): void
    {
        Log::debug(sprintf(
            'Increased infusion of token %s-%s by %s.',
            $this->event->collectionId,
            $this->event->tokenId,
            $this->event->amount,
        ));
    }

    public function broadcast(): void
    {
        TokenInfused::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }

    protected function increaseInfusedValue(string $current, string $amount): string
    {
        return gmp_strval(gmp_add(gmp_init($current), gmp_init($amount)));
    }
}
