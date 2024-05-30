<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Enjin\Platform\Support\Account;
use Illuminate\Support\Arr;

class Thawed extends Event implements PolkadartEvent
{
    public readonly ?string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $collectionId;
    public readonly ?string $tokenId;
    public readonly ?string $account;
    public readonly string $freezeType;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->collectionId = $self->getValue($data, ['collection_id', 'FreezeOf<T>.collection_id']);
        $self->freezeType = is_string($type = $self->getValue($data, ['freeze_type', 'FreezeOf<T>.freeze_type'])) ? $type : array_key_first($type);
        $self->tokenId = $self->getTokenId($data, $self->freezeType);
        $self->account = Account::parseAccount($self->getAccount($data, $self->freezeType));

        return $self;
    }

    public function getParams(): array
    {
        return [
            ['type' => 'collection_id', 'value' => $this->collectionId],
            ['type' => 'token_id', 'value' => $this->tokenId],
            ['type' => 'account', 'value' => $this->account],
            ['type' => 'freeze_type', 'value' => $this->freezeType],
        ];
    }

    protected function getTokenId(array $data, string $freezeType): ?string
    {
        if (!in_array($freezeType, ['Token', 'TokenAccount'])) {
            return null;
        }

        // We can use only freeze_type.Token.token_id when Substrate is upgraded
        return $freezeType === 'Token'
            ? $this->getValue($data, ['freeze_type.Token.token_id', 'FreezeOf<T>.freeze_type.Token.token_id'])
            : $this->getValue($data, ['freeze_type.TokenAccount.token_id', 'FreezeOf<T>.freeze_type.TokenAccount.token_id']);
    }

    protected function getAccount(array $data, string $freezeType): string|array|null
    {
        if (!in_array($freezeType, ['CollectionAccount', 'TokenAccount'])) {
            return null;
        }

        return $freezeType === 'CollectionAccount'
            ? $this->getValue($data, ['freeze_type.CollectionAccount', 'FreezeOf<T>.freeze_type.CollectionAccount'])
            : $this->getValue($data, ['freeze_type.TokenAccount.account_id', 'FreezeOf<T>.freeze_type.TokenAccount.account_id']);
    }
}

/* Example 1
    [
        "phase" => [
            "ApplyExtrinsic" => 2,
        ],
        "event" => [
            "MultiTokens" => [
                "Thawed" => [
                    "collection_id" => "10133",
                    "freeze_type" => [
                        "TokenAccount" => [
                            "token_id" => "1",
                            "account_id" => "d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d",
                        ],
                    ],
                ],
            ],
         ],
         "topics" => [],
    ]
 */
