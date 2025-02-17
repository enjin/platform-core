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

    #[\Override]
    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->collectionId = $self->getValue($data, 'T::CollectionId');
        $self->tokenId = $self->getValue($data, 'T::TokenId');
        $self->accountId = Account::parseAccount($self->getValue($data, ['RootOrSigned<T::AccountId>.Signed', 'T::AccountId']));
        $self->amount = $self->getValue($data, 'BalanceOf<T>');

        return $self;
    }

    #[\Override]
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
{
  "event": {
    "MultiTokens": {
      "Infused": {
        "BalanceOf<T>": "100000",
        "RootOrSigned<T::AccountId>": {
          "Signed": [
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
          ]
        },
        "T::CollectionId": "100015",
        "T::TokenId": "1"
      }
    }
  },
  "phase": {
    "ApplyExtrinsic": 2
  },
  "topics": []
}
*/
