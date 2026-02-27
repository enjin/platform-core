<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class TokenGroupDestroyed extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $tokenGroupId;

    #[\Override]
    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->tokenGroupId = $self->getValue($data, 'T::TokenGroupId');

        return $self;
    }

    #[\Override]
    public function getParams(): array
    {
        return [
            ['type' => 'token_group_id', 'value' => $this->tokenGroupId],
        ];
    }
}
