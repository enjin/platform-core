<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Support\Arr;

class Deposit extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $who;
    public readonly string $amount;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->who = SS58Address::getPublicKey($self->getValue($data, ['who', 'T::AccountId']));
        $self->amount = $self->getValue($data, ['amount', 'T::Balance']);

        return $self;
    }

    public function getPallet(): string
    {
        return $this->module;
    }

    public function getParams(): array
    {
        return [
            ['type' => 'who', 'value' => $this->who],
            ['type' => 'amount', 'value' => $this->amount],
        ];
    }
}
