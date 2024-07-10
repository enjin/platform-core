<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class Reserved extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $collectionId;
    public readonly string $tokenId;
    public readonly string $accountId;
    public readonly string $amount;
    public readonly string $reserveId;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->collectionId = $self->getValue($data, ['collection_id', 'T::CollectionId']);
        $self->tokenId = $self->getValue($data, ['token_id', 'T::TokenId']);
        $self->accountId = Account::parseAccount($self->getValue($data, ['account_id', 'T::AccountId']));
        $self->amount = $self->getValue($data, ['amount', 'T::TokenBalance']);
        $self->reserveId = HexConverter::hexToString(
            is_string($value = $self->getValue($data, ['reserve_id.Some', 'Option<T::ReserveIdentifierType>']))
                ? $value
                : HexConverter::bytesToHex($value)
        );

        return $self;
    }

    public function getParams(): array
    {
        return [
            ['type' => 'collection_id', 'value' => $this->collectionId],
            ['type' => 'token_id', 'value' => $this->tokenId],
            ['type' => 'account_id', 'value' => $this->accountId],
            ['type' => 'amount', 'value' => $this->amount],
            ['type' => 'reserve_id', 'value' => $this->reserveId],
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
        "Reserved" => array:5 [▼
          "T::CollectionId" => "77160"
          "T::TokenId" => "1"
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
          "T::TokenBalance" => "10"
          "Option<T::ReserveIdentifierType>" => array:8 [▼
            0 => 109
            1 => 97
            2 => 114
            3 => 107
            4 => 116
            5 => 112
            6 => 108
            7 => 99
          ]
        ]
      ]
    ]
    "topics" => []
  ]
*/
