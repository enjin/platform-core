<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class TokenMutated extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $collectionId;
    public readonly string $tokenId;
    public readonly string $behavior;
    public readonly bool $isCurrency;
    public readonly ?bool $listingForbidden;
    public readonly ?bool $anyoneCanInfuse;
    public readonly ?string $tokenName;
    public readonly ?string $beneficiary;
    public readonly ?string $percentage;

    #[\Override]
    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->collectionId = $self->getValue($data, 'T::CollectionId');
        $self->tokenId = $self->getValue($data, 'T::TokenId');
        $self->listingForbidden = $self->getValue($data, 'T::TokenMutation.listing_forbidden.SomeMutation');
        $self->behavior = is_string($behavior = $self->getValue($data, 'T::TokenMutation.behavior')) ? $behavior : array_key_first($behavior);
        $self->isCurrency = $self->getValue($data, 'T::TokenMutation.behavior.SomeMutation') === ['IsCurrency' => null];

        // TODO: After v1020 we now have an array of beneficiaries for now we will just use the first one
        $self->beneficiary = Account::parseAccount($self->getValue($data, ['T::TokenMutation.behavior.SomeMutation.HasRoyalty.beneficiary', 'T::TokenMutation.behavior.SomeMutation.HasRoyalty.0.beneficiary']));
        $self->percentage = $self->getValue($data, ['T::TokenMutation.behavior.SomeMutation.HasRoyalty.percentage', 'T::TokenMutation.behavior.SomeMutation.HasRoyalty.0.percentage']);

        $self->anyoneCanInfuse = $self->getValue($data, 'T::TokenMutation.anyone_can_infuse.SomeMutation');
        $self->tokenName = is_array($s = $self->getValue($data, 'T::TokenMutation.name.SomeMutation')) ? HexConverter::bytesToHex($s) : $s;

        return $self;
    }

    #[\Override]
    public function getParams(): array
    {
        return [
            ['type' => 'collection_id', 'value' => $this->collectionId],
            ['type' => 'token_id', 'value' => $this->tokenId],
            ['type' => 'listing_forbidden', 'value' => $this->listingForbidden],
            ['type' => 'behavior', 'value' => $this->behavior],
            ['type' => 'is_currency', 'value' => $this->isCurrency],
            ['type' => 'beneficiary', 'value' => $this->beneficiary],
            ['type' => 'percentage', 'value' => $this->percentage],
            ['type' => 'anyone_can_infuse', 'value' => $this->anyoneCanInfuse],
            ['type' => 'token_name', 'value' => $this->tokenName],
        ];
    }
}

/* Example 1
{
  "event": {
    "MultiTokens": {
      "TokenMutated": {
        "T::CollectionId": "100015",
        "T::TokenId": "1",
        "T::TokenMutation": {
          "anyone_can_infuse": {
            "NoMutation": null
          },
          "behavior": {
            "SomeMutation": {
              "HasRoyalty": [
                {
                  "beneficiary": [
                    212,
                    53,
                    147,
                    199,
                    21,
                    253,
                    211,
                    28,
                    97,
                    20,
                    26,
                    189,
                    4,
                    169,
                    159,
                    214,
                    130,
                    44,
                    133,
                    88,
                    133,
                    76,
                    205,
                    227,
                    154,
                    86,
                    132,
                    231,
                    165,
                    109,
                    162,
                    125
                  ],
                  "percentage": 1000000
                }
              ]
            }
          },
          "listing_forbidden": {
            "NoMutation": null
          },
          "name": {
            "NoMutation": null
          }
        }
      }
    }
  },
  "phase": {
    "ApplyExtrinsic": 2
  },
  "topics": []
}
 */
