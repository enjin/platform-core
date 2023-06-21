<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events;

use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;

interface SubstrateEvent
{
    public function run(PolkadartEvent $event, Block $block, Codec $codec);
}
