<?php

namespace Enjin\Platform\FuelTanks\Services\Processor\Substrate\Events\Implementations\FuelTanks;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks\AccountRuleDataRemoved as AccountRuleDataPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;

class AccountRuleDataRemoved extends SubstrateEvent
{
    /** @var AccountRuleDataPolkadart */
    protected Event $event;

    /**
     * Handle the account rule data removed event.
     */
    public function run(): void
    {
        // TODO: Removes tracking data associated to a dispatch rule (doesn't remove the rule)
        // Not sure how to do that yet, we would have to know the rule structure on chain.
        // Maybe we should query the storage for this one? That would make it slower though
    }

    public function log(): void {}

    public function broadcast(): void {}
}
