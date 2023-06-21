<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;
use Illuminate\Support\Arr;

class Generic implements PolkadartExtrinsic
{
    public readonly ?string $signer;
    public readonly string $hash;
    public readonly int $index;
    public readonly string $module;
    public readonly string $call;
    public readonly ?array $params;

    public static function fromChain(array $data): self
    {
        $self = new self();
        $self->signer = Arr::get($data, 'signature.address.Id');
        $self->hash = Arr::get($data, 'extrinsic_hash');
        $self->module = array_key_first(Arr::get($data, 'call'));
        $self->call = is_string($callId = Arr::get($data, 'call.' . $self->module)) ? $callId : array_key_first($callId);
        $self->params = Arr::get($data, 'call.' . $self->module . '.' . $self->call);

        return $self;
    }
}

/*
*/
