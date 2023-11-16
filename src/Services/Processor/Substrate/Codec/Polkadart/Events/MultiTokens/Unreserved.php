<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class Unreserved implements PolkadartEvent
{
    public readonly string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $collectionId;
    public readonly string $tokenId;
    public readonly string $accountId;
    public readonly string $amount;
    public readonly string $reserveId;

    public static function fromChain(array $data): self
    {
        $self = new self();
        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->collectionId = Arr::get($data, 'event.MultiTokens.Unreserved.collection_id');
        $self->tokenId = Arr::get($data, 'event.MultiTokens.Unreserved.token_id');
        $self->accountId = Arr::get($data, 'event.MultiTokens.Unreserved.account_id');
        $self->amount = Arr::get($data, 'event.MultiTokens.Unreserved.amount');
        $self->reserveId = Arr::get($data, 'event.MultiTokens.Unreserved.reserve_id.Some');

        return $self;
    }

    public function getPallet(): string
    {
        return $this->module;
    }

    public function getParams(): array
    {
        return [
            ['type' => 'collection_id', 'value' => $this->collectionId],
            ['type' => 'token_id', 'value' => $this->tokenId],
            ['type' => 'account_id', 'value' => $this->accountId],
            ['type' => 'amount', 'value' => $this->amount],
            ['type' => 'reserve_id', 'value' => $this->reserveId],
        ];
    }
}

/* Example 1
[
    'phase' => [
        'ApplyExtrinsic' => 2,
    ],
    'event' => [
        'MultiTokens' => [
            'Unreserved' => [
                'collection_id' => '2100',
                'token_id' => '1',
                'account_id' => '768565957dc61edae9876989e45b78f54f6118c9852adbdea5d53dcda2a7621c',
                'amount' => '707',
                'reserve_id' => [
                    'Some' => '6d61726b74706c63',
                ],
            ],
        ],
    ],
    'topics' => [],,
]
*/
