<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class Transfer implements PolkadartEvent
{
    public readonly string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $from;
    public readonly string $to;
    public readonly string $amount;

    public static function fromChain(array $data): PolkadartEvent
    {
        $self = new self();
        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->from = Arr::get($data, 'event.Balances.Transfer.from');
        $self->to = Arr::get($data, 'event.Balances.Transfer.to');
        $self->amount = Arr::get($data, 'event.Balances.Transfer.amount');

        return $self;
    }

    public function getPallet(): string
    {
        return $this->module;
    }

    public function getParams(): array
    {
        return [
            ['type' => 'from', 'value' => $this->from],
            ['type' => 'to', 'value' => $this->to],
            ['type' => 'amount', 'value' => $this->amount],
        ];
    }
}
