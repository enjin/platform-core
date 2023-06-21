<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart;

interface PolkadartExtrinsic
{
    public static function fromChain(array $data): self;
}
