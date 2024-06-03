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
    [
        "phase" => [
            "ApplyExtrinsic" => 5,
        ],
        "event" => [
            "MultiTokens" => [
                "Unapproved" => [
                    "collection_id" => "10685",
                    "token_id" => [
                        "None" => null,
                    ],
                    "owner" => "d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d",
                    "operator" => "8eaf04151687736326c9fea17e25fc5287613693c912909cb226aa4794f26a48",
                ],
            ],
        ],
        "topics" => [],
    ]
*/
