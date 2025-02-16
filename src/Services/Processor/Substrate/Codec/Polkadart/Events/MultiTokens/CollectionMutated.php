<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class CollectionMutated extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $collectionId;
    public readonly ?string $owner;
    public readonly string $royalty;
    public readonly ?string $beneficiary;
    public readonly ?string $percentage;
    public readonly ?array $explicitRoyaltyCurrencies;

    #[\Override]
    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->collectionId = $self->getValue($data, 'T::CollectionId');
        $self->owner = Account::parseAccount($self->getValue($data, 'T::CollectionMutation.owner'));
        $self->royalty = $self->getRoyalty($data);
        $self->beneficiary = Account::parseAccount($self->getBeneficiary($data, $self->royalty));
        $self->percentage = $self->getPercentage($data, $self->royalty);
        $self->explicitRoyaltyCurrencies = $self->getValue($data, 'T::CollectionMutation.explicit_royalty_currencies');

        return $self;
    }

    #[\Override]
    public function getParams(): array
    {
        return [
            ['type' => 'collection_id', 'value' => $this->collectionId],
            ['type' => 'owner', 'value' => $this->owner],
            ['type' => 'royalty', 'value' => $this->royalty],
            ['type' => 'beneficiary', 'value' => $this->beneficiary],
            ['type' => 'percentage', 'value' => $this->percentage],
            ['type' => 'explicit_royalty_currencies', 'value' => is_array($this->explicitRoyaltyCurrencies)
                ? json_encode($this->explicitRoyaltyCurrencies)
                : $this->explicitRoyaltyCurrencies,
            ],
        ];
    }

    protected function getRoyalty($data): string
    {
        $royalty = $this->getValue($data, 'T::CollectionMutation.royalty');

        if ($royalty === null || $royalty === 'NoMutation') {
            return 'NoMutation';
        }

        return array_key_first($royalty);
    }

    protected function getBeneficiary($data, $royalty): string|array|null
    {
        if ($royalty === 'NoMutation') {
            return null;
        }

        // TODO: After v1020 we now have an array of beneficiaries for now we will just use the first one
        return $this->getValue($data, ['T::CollectionMutation.royalty.SomeMutation.beneficiary', 'T::CollectionMutation.royalty.SomeMutation.0.beneficiary']);
    }

    protected function getPercentage($data, $royalty): ?string
    {
        if ($royalty === 'NoMutation') {
            return null;
        }

        // TODO: After v1020 we now have an array of beneficiaries for now we will just use the first one
        return $this->getValue($data, ['T::CollectionMutation.royalty.SomeMutation.percentage', 'T::CollectionMutation.royalty.SomeMutation.0.percentage']);
    }
}

/* Example 1
  [▼
    "phase" => array:1 [▶]
    "event" => array:1 [▼
      "MultiTokens" => array:1 [▼
        "CollectionMutated" => array:2 [▼
          "T::CollectionId" => "77160"
          "T::CollectionMutation" => array:3 [▼
            "owner" => array:32 [▼
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
            "royalty" => array:1 [▼
              "SomeMutation" => array:2 [▼
                "beneficiary" => array:32 [▼
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
                "percentage" => 10000000
              ]
            ]
            "explicit_royalty_currencies" => array:1 [▼
              0 => array:2 [▼
                "collection_id" => "0"
                "token_id" => "0"
              ]
            ]
          ]
        ]
      ]
    ]
    "topics" => []
  ]
*/
