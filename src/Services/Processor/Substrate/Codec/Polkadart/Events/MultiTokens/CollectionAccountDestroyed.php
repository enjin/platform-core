<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class CollectionAccountDestroyed implements PolkadartEvent
{
    public readonly string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $collectionId;
    public readonly string $account;

    public static function fromChain(array $data): self
    {
        $self = new self();
        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->collectionId = Arr::get($data, 'event.MultiTokens.CollectionAccountDestroyed.collection_id');
        $self->account = Arr::get($data, 'event.MultiTokens.CollectionAccountDestroyed.account_id');

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
            ['type' => 'account', 'value' => $this->account],
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
                "CollectionAccountDestroyed" => [
                    "collection_id" => "10133",
                    "account_id" => "8eaf04151687736326c9fea17e25fc5287613693c912909cb226aa4794f26a48",
                ],
            ],
        ],
        "topics" => [],
    ]
 */
