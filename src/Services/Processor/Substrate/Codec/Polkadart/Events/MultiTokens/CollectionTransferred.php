<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class CollectionTransferred extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $collectionId;
    public readonly string $owner;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->collectionId = $self->getValue($data, 'T::CollectionId');
        $self->owner = Account::parseAccount($self->getValue($data, 'T::AccountId'));

        return $self;
    }

    public function getParams(): array
    {
        return [
            ['type' => 'collection_id', 'value' => $this->collectionId],
            ['type' => 'owner', 'value' => $this->owner],
        ];
    }
}

/* Example 1
  [▼
    "phase" => array:1 [▼
      "ApplyExtrinsic" => 2
    ]
    "event" => array:1 [▼
      "MultiTokens" => array:1 [▼
        "CollectionTransferred" => array:2 [▼
          "T::CollectionId" => "77160"
          "T::AccountId" => array:32 [▼
            0 => 144
            1 => 181
            2 => 171
            3 => 32
            4 => 92
            5 => 105
            6 => 116
            7 => 201
            8 => 234
            9 => 132
            10 => 27
            11 => 230
            12 => 136
            13 => 134
            14 => 70
            15 => 51
            16 => 220
            17 => 156
            18 => 168
            19 => 163
            20 => 87
            21 => 132
            22 => 62
            23 => 234
            24 => 207
            25 => 35
            26 => 20
            27 => 100
            28 => 153
            29 => 101
            30 => 254
            31 => 34
          ]
        ]
      ]
    ]
    "topics" => []
  ]
*/
