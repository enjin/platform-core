<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class Infused extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $collectionId;
    public readonly string $tokenId;
    public readonly string $accountId;
    public readonly string $amount;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->collectionId = $self->getValue($data, 'T::CollectionId');
        $self->tokenId = $self->getValue($data, 'T::TokenId');
        $self->accountId = Account::parseAccount($self->getValue($data, 'T::AccountId'));
        $self->amount = $self->getValue($data, 'BalanceOf<T>');

        return $self;
    }

    public function getParams(): array
    {
        return [
            ['type' => 'collection_id', 'value' => $this->collectionId],
            ['type' => 'token_id', 'value' => $this->tokenId],
            ['type' => 'account_id', 'value' => $this->accountId],
            ['type' => 'amount', 'value' => $this->amount],
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
      "Infused" => array:4 [▼
        "T::CollectionId" => "91542"
        "T::TokenId" => "1"
        "T::AccountId" => array:32 [▼
          0 => 198
          1 => 96
          2 => 254
          3 => 244
          4 => 192
          5 => 146
          6 => 110
          7 => 56
          8 => 40
          9 => 57
          10 => 210
          11 => 12
          12 => 174
          13 => 230
          14 => 212
          15 => 227
          16 => 173
          17 => 180
          18 => 210
          19 => 126
          20 => 198
          21 => 107
          22 => 34
          23 => 62
          24 => 214
          25 => 69
          26 => 104
          27 => 69
          28 => 25
          29 => 110
          30 => 62
          31 => 121
        ]
        "BalanceOf<T>" => "1000"
      ]
    ]
  ]
  "topics" => []
]
*/
