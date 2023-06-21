<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class Burned implements PolkadartEvent
{
    public readonly string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $collectionId;
    public readonly string $tokenId;
    public readonly string $account;
    public readonly string $amount;

    public static function fromChain(array $data): self
    {
        $self = new self();
        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->collectionId = Arr::get($data, 'event.MultiTokens.Burned.collection_id');
        $self->tokenId = Arr::get($data, 'event.MultiTokens.Burned.token_id');
        $self->account = Arr::get($data, 'event.MultiTokens.Burned.account_id');
        $self->amount = Arr::get($data, 'event.MultiTokens.Burned.amount');

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
            ['type' => 'account', 'value' => $this->account],
            ['type' => 'amount', 'value' => $this->amount],
        ];
    }
}

/* Example 1
    [
        "phase" => [
            "ApplyExtrinsic" => 2,
        ],
        "event" => [
            "MultiTokens" => [
                "Burned" => [
                    "collection_id" => "10133",
                    "token_id" => "1",
                    "account_id" => "d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d",
                    "amount" => "1",
                ],
            ],
        ],
        "topics" => [],
    ]
*/
