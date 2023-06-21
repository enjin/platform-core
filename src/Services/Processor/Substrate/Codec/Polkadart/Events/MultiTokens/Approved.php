<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class Approved implements PolkadartEvent
{
    public readonly string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $collectionId;
    public readonly ?string $tokenId;
    public readonly string $owner;
    public readonly string $operator;
    public readonly ?string $amount;
    public readonly ?string $expiration;

    public static function fromChain(array $data): self
    {
        $self = new self();
        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->collectionId = Arr::get($data, 'event.MultiTokens.Approved.collection_id');
        $self->tokenId = Arr::get($data, 'event.MultiTokens.Approved.token_id.Some');
        $self->owner = Arr::get($data, 'event.MultiTokens.Approved.owner');
        $self->operator = Arr::get($data, 'event.MultiTokens.Approved.operator');
        $self->amount = Arr::get($data, 'event.MultiTokens.Approved.amount.Some');
        $self->expiration = Arr::get($data, 'event.MultiTokens.Approved.expiration.Some');

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
            ['type' => 'owner', 'value' => $this->owner],
            ['type' => 'operator', 'value' => $this->operator],
            ['type' => 'amount', 'value' => $this->amount],
            ['type' => 'expiration', 'value' => $this->expiration],
        ];
    }
}

/* Example 1
    [
        "phase" => [
            "ApplyExtrinsic" => 22,
        ],
        "event" => [
            "MultiTokens" => [
                "Approved" => [
                    "collection_id" => "6499",
                    "token_id" => [
                        "None" => null,
                    ],
                    "owner" => "68b427dda4f3894613e113b570d5878f3eee981196133e308c0a82584cf2e160",
                    "operator" => "d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d",
                    "amount" => [
                        "None" => null,
                    ],
                    "expiration" => [
                        "None" => null,
                    ],
                ],
            ],
        ],
        "topics" => [],
    ]
 */
