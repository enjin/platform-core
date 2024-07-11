<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class Minted extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $collectionId;
    public readonly string $tokenId;
    public readonly string $issuer;
    public readonly string $recipient;
    public readonly string $amount;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->collectionId = $self->getValue($data, ['collection_id', 'T::CollectionId']);
        $self->tokenId = $self->getValue($data, ['token_id', 'T::TokenId']);
        $self->issuer = Account::parseAccount($self->getValue($data, ['issuer.Signed', 'RootOrSigned<T::AccountId>.Signed']));
        $self->recipient = Account::parseAccount($self->getValue($data, ['recipient', 'T::AccountId']));
        $self->amount = $self->getValue($data, ['amount', 'T::TokenBalance']);

        return $self;
    }

    public function getParams(): array
    {
        return [
            ['type' => 'collection_id', 'value' => $this->collectionId],
            ['type' => 'token_id', 'value' => $this->tokenId],
            ['type' => 'issuer', 'value' => $this->issuer],
            ['type' => 'recipient', 'value' => $this->recipient],
            ['type' => 'amount', 'value' => $this->amount],
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
      "Minted" => array:5 [▼
        "T::CollectionId" => "77160"
        "T::TokenId" => "0"
        "RootOrSigned<T::AccountId>" => array:1 [▼
          "Signed" => array:32 [▼
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
        "T::TokenBalance" => "10000"
      ]
    ]
  ]
  "topics" => []
]
 */
