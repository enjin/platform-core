<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\SS58Address;
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

    public function getPallet(): string
    {
        return $this->module;
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
    [
        "phase" => [
            "ApplyExtrinsic" => 2,
        ],
        "event" => [
            "MultiTokens" => [
                "Minted" => [
                    "collection_id" => "9248",
                    "token_id" => "1",
                    "issuer" => [
                        "Signed" => "d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d",
                    ],
                    "recipient" => "d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d",
                    "amount" => "1",
                ],
            ],
        ],
        "topics" => []
    ]
 */
