<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class AttributeSet implements PolkadartEvent
{
    public readonly string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $collectionId;
    public readonly ?string $tokenId;
    public readonly string $key;
    public readonly string $value;

    public static function fromChain(array $data): self
    {
        $self = new self();
        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->collectionId = Arr::get($data, 'event.MultiTokens.AttributeSet.collection_id');
        $self->tokenId = Arr::get($data, 'event.MultiTokens.AttributeSet.token_id.Some');
        $self->key = is_string($key = Arr::get($data, 'event.MultiTokens.AttributeSet.key')) ? $key : HexConverter::bytesToHex($key);
        $self->value = is_string($value = Arr::get($data, 'event.MultiTokens.AttributeSet.value')) ? $value : HexConverter::bytesToHex($value);

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
            ['type' => 'key', 'value' => $this->key],
            ['type' => 'value', 'value' => $this->value],
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
                "AttributeSet" => [
                    "collection_id" => "9248",
                    "token_id" => [
                        "None" => null,
                    ],
                    "key" => [
                         0 => 110,
                         1 => 97,
                         ...,
                    ],
                    "value" => [
                         0 => 84,
                         1 => 101,
                         ...,
                    ],
                ],
            ],
        ],
        "topics" => [],
    ]
 */
