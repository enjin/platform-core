<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\SS58Address;
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
        $self->reserveId = is_string($value = $self->getValue($data, ['reserve_id.Some', 'Option<T::ReserveIdentifierType>'])) ? $value : HexConverter::bytesToHex($value);

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
            ['type' => 'account_id', 'value' => $this->accountId],
            ['type' => 'amount', 'value' => $this->amount],
            ['type' => 'reserve_id', 'value' => $this->reserveId],
        ];
    }
}

/* Example 1
[
    'phase' => [
        'ApplyExtrinsic' => 2,
    ],
    'event' => [
        'MultiTokens' => [
            'Reserved' => [
                'collection_id' => '2128',
                'token_id' => '102',
                'account_id' => 'ae7e75ae003949bb2905591d8ba7aaab1d4aaf6dc5567874930e1be3ff51fb6f',
                'amount' => '10',
                'reserve_id' => [
                    'Some' => '6d61726b74706c63',
                ],
            ],
        ],
    ],
    'topics' => [],,
]
*/
