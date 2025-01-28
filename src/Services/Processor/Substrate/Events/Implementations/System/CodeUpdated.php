<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\System;

use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\System\CodeUpdated as CodeUpdatedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Events\Substrate\System\CodeUpdated as CodeUpdatedEvent;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CodeUpdated extends SubstrateEvent
{
    /** @var CodeUpdatedPolkadart */
    protected Event $event;

    public function run(): void
    {
        Cache::forget(PlatformCache::SPEC_VERSION->key());
        Cache::forget(PlatformCache::TRANSACTION_VERSION->key());
    }

    public function log(): void
    {
        Log::debug(sprintf(
            'Runtime code updated at %s',
            $this->event->extrinsicIndex,
        ));
    }

    public function broadcast(): void
    {
        CodeUpdatedEvent::safeBroadcast($this->event);
    }
}
