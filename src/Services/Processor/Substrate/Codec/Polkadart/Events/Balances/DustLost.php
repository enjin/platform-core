<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Balances;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class DustLost extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $account;
    public readonly string $amount;

    #[\Override]
    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->account = Account::parseAccount($self->getValue($data, 'T::AccountId'));
        $self->amount = $self->getValue($data, 'T::Balance');

        return $self;
    }

    #[\Override]
    public function getParams(): array
    {
        return [
            ['type' => 'account', 'value' => $this->account],
            ['type' => 'amount', 'value' => $this->amount],
        ];
    }
}
