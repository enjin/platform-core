<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class AttributeRemoved extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $collectionId;
    public readonly ?string $tokenId;
    public readonly string $key;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->collectionId = $self->getValue($data, ['collection_id', 'T::CollectionId']);
        $self->tokenId = $self->getValue($data, ['token_id.Some', 'Option<T::TokenId>']);
        $self->key = (is_string($value = $self->getValue($data, ['key', 'T::AttributeKey'])) ? $value : HexConverter::bytesToHex($value));

        return $self;
    }

    public function getParams(): array
    {
        return [
            ['type' => 'collection_id', 'value' => $this->collectionId],
            ['type' => 'token_id', 'value' => $this->tokenId],
            ['type' => 'key', 'value' => $this->key],
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
                "AttributeRemoved" => [
                    "collection_id" => "9248",
                    "token_id" => [
                        "None" => null,
                    ],
                    "key" => [
                         0 => 110,
                         1 => 97,
                         ...,
                    ],
                ],
            ],
        ],
        "topics" => [],
    ]
 */
