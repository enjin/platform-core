<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class TokenCreated implements PolkadartEvent
{
    public readonly string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $collectionId;
    public readonly string $tokenId;
    public readonly string $issuer;
    public readonly string $initialSupply;

    public static function fromChain(array $data): self
    {
        $self = new self();
        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->collectionId = Arr::get($data, 'event.MultiTokens.TokenCreated.collection_id');
        $self->tokenId = Arr::get($data, 'event.MultiTokens.TokenCreated.token_id');
        $self->issuer = Arr::get($data, 'event.MultiTokens.TokenCreated.issuer.Signed');
        $self->initialSupply = Arr::get($data, 'event.MultiTokens.TokenCreated.initial_supply');

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
            ['type' => 'issuer', 'value' => $this->issuer],
            ['type' => 'initial_supply', 'value' => $this->initialSupply],
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
                "TokenCreated" => [
                    "collection_id" => "9248",
                    "token_id" => "1",
                    "issuer" => [
                        "Signed" => "d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d",
                    ],
                    "initial_supply" => "1",
                ],
            ],
        ],
        "topics" => [],
    ]
*/
