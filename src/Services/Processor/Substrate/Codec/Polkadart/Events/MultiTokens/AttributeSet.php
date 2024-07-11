<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class AttributeSet extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
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
        $self->collectionId = $self->getValue($data, ['collection_id', 'T::CollectionId']);
        $self->tokenId = $self->getValue($data, ['token_id.Some', 'Option<T::TokenId>']);
        $self->key = is_string($value = $self->getValue($data, ['key', 'T::AttributeKey'])) ? $value : HexConverter::bytesToHex($value);
        $self->value = is_string($value = $self->getValue($data, ['value', 'T::AttributeValue'])) ? $value : HexConverter::bytesToHex($value);

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
    array:3 [▼
      "phase" => array:1 [▼
        "ApplyExtrinsic" => 2
      ]
      "event" => array:1 [▼
        "MultiTokens" => array:1 [▼
          "AttributeSet" => array:4 [▼
            "T::CollectionId" => "77159"
            "Option<T::TokenId>" => null
            "T::AttributeKey" => array:4 [▼
              0 => 110
              1 => 97
              2 => 109
              3 => 101
            ]
            "T::AttributeValue" => array:5 [▼
              0 => 84
              1 => 101
              2 => 115
              3 => 116
              4 => 50
            ]
          ]
        ]
      ]
      "topics" => []
    ]
 */
