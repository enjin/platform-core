<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart;

interface PolkadartEvent
{
    public static function fromChain(array $data): self;

    public function getPallet(): string;

    public function getParams(): array;
}
