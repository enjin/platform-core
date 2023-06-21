<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class Transferred implements PolkadartEvent
{
    public readonly string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $collectionId;
    public readonly string $tokenId;
    public readonly string $operator;
    public readonly string $from;
    public readonly string $to;
    public readonly string $amount;

    public static function fromChain(array $data): PolkadartEvent
    {
        $self = new self();
        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->collectionId = Arr::get($data, 'event.MultiTokens.Transferred.collection_id');
        $self->tokenId = Arr::get($data, 'event.MultiTokens.Transferred.token_id');
        $self->operator = Arr::get($data, 'event.MultiTokens.Transferred.operator');
        $self->from = Arr::get($data, 'event.MultiTokens.Transferred.from');
        $self->to = Arr::get($data, 'event.MultiTokens.Transferred.to');
        $self->amount = Arr::get($data, 'event.MultiTokens.Transferred.amount');

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
            ['type' => 'operator', 'value' => $this->operator],
            ['type' => 'from', 'value' => $this->from],
            ['type' => 'to', 'value' => $this->to],
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
                "Transferred" => [
                    "collection_id" => "10133",
                    "token_id" => "1",
                    "operator" => "d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d",
                    "from" => "d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d",
                    "to" => "8eaf04151687736326c9fea17e25fc5287613693c912909cb226aa4794f26a48",
                    "amount" => "1",
                ],
            ],
        ],
        "topics" => [],
    ]
 */
