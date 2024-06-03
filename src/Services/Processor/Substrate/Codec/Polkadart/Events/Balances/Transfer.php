<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class Transfer extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $from;
    public readonly string $to;
    public readonly string $amount;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->from = Account::parseAccount($self->getValue($data, ['from', '0']));
        $self->to = Account::parseAccount($self->getValue($data, ['to', '1']));
        $self->amount = $self->getValue($data, ['amount', '2']);

        return $self;
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
