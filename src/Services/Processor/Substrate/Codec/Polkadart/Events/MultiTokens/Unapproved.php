<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class Unapproved extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $collectionId;
    public readonly ?string $tokenId;
    public readonly string $owner;
    public readonly string $operator;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->collectionId = $self->getValue($data, ['collection_id', '0']);
        $self->tokenId = $self->getValue($data, ['token_id.Some', '1']);
        $self->owner = Account::parseAccount($self->getValue($data, ['owner', '2']));
        $self->operator = Account::parseAccount($self->getValue($data, ['operator', '3']));

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
        "Unapproved" => array:4 [▼
          0 => "77160"
          1 => "1"
          2 => array:32 [▼
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
          3 => array:32 [▼
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
