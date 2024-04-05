<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Extrinsics;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartExtrinsic;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Support\Arr;

class Extrinsic implements PolkadartExtrinsic
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

        $self->signer = SS58Address::getPublicKey(Arr::get($data, 'signature.address.Id'));
        $self->hash = HexConverter::prefix($data['extrinsic_hash'] ?? $data['hash']);
        $self->module = array_key_first($call = self::callOrCalls($data));
        $self->call = is_string($callId = Arr::get($call, $self->module)) ? $callId : array_key_first($callId);
        $self->params = Arr::get($call, $self->module . '.' . $self->call);

        return $self;
    }

    public static function callOrCalls(array $data): array
    {
        if (isset($data['call'])) {
            return $data['call'];
        }

        if (isset($data['calls'])) {
            return $data['calls'];
        }

        return [];
    }
}

/*
*/
