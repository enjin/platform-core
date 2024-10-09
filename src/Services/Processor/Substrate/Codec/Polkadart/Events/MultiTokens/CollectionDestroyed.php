<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class CollectionDestroyed extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $collectionId;
    public readonly string $caller;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->collectionId = $self->getValue($data, 'T::CollectionId');
        $self->caller = Account::parseAccount($self->getValue($data, 'T::AccountId'));

        return $self;
    }

    public function getParams(): array
    {
        return [
            ['type' => 'collection_id', 'value' => $this->collectionId],
            ['type' => 'caller', 'value' => $this->caller],
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
          "CollectionDestroyed" => array:2 [▼
            "T::CollectionId" => "77159"
            "T::AccountId" => array:32 [▼
              0 => 212
              1 => 53
              2 => 147
              3 => 199
              4 => 21
              5 => 253
              6 => 211
              7 => 28
              8 => 97
              9 => 20
              10 => 26
              11 => 189
              12 => 4
              13 => 169
              14 => 159
              15 => 214
              16 => 130
              17 => 44
              18 => 133
              19 => 88
              20 => 133
              21 => 76
              22 => 205
              23 => 227
              24 => 154
              25 => 86
              26 => 132
              27 => 231
              28 => 165
              29 => 109
              30 => 162
              31 => 125
            ]
          ]
        ]
      ]
      "topics" => []
    ]
 */
