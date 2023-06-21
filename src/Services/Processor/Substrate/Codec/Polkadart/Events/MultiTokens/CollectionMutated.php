<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens;

use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\PolkadartEvent;
use Illuminate\Support\Arr;

class CollectionMutated implements PolkadartEvent
{
    public readonly string $extrinsicIndex;
    public readonly string $module;
    public readonly string $name;
    public readonly string $collectionId;
    public readonly ?string $owner;
    public readonly string $royalty;
    public readonly ?string $beneficiary;
    public readonly ?string $percentage;
    public readonly ?array $explicitRoyaltyCurrencies;

    public static function fromChain(array $data): self
    {
        $self = new self();
        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->collectionId = Arr::get($data, 'event.MultiTokens.CollectionMutated.collection_id');
        $self->owner = Arr::get($data, 'event.MultiTokens.CollectionMutated.mutation.owner.Some');
        $self->royalty = $royalty = self::getRoyalty($data);
        $self->beneficiary = self::getBeneficiary($data, $royalty);
        $self->percentage = self::getPercentage($data, $royalty);
        $self->explicitRoyaltyCurrencies = Arr::get($data, 'event.MultiTokens.CollectionMutated.mutation.explicit_royalty_currencies.Some');

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

    protected static function getRoyalty($data): string
    {
        $royalty = Arr::get($data, 'event.MultiTokens.CollectionMutated.mutation.royalty');

        if ($royalty === null || $royalty === 'NoMutation') {
            return 'NoMutation';
        }

        return array_key_first($royalty);
    }

    protected static function getBeneficiary($data, $royalty): ?string
    {
        if ($royalty === 'NoMutation') {
            return null;
        }

        return Arr::get($data, 'event.MultiTokens.CollectionMutated.mutation.royalty.SomeMutation.Some.beneficiary');
    }

    protected static function getPercentage($data, $royalty): ?string
    {
        if ($royalty === 'NoMutation') {
            return null;
        }

        return Arr::get($data, 'event.MultiTokens.CollectionMutated.mutation.royalty.SomeMutation.Some.percentage');
    }
}

/* Example 1
    [
        "phase" => [
            "ApplyExtrinsic" => 2,
        ],
        "event" => [
            "MultiTokens" => [
                "CollectionMutated" => [
                    "collection_id" => "10685",
                    "mutation" => [
                        "owner" => [
                            "Some" => "8eaf04151687736326c9fea17e25fc5287613693c912909cb226aa4794f26a48",
                        ],
                        "royalty" => [
                            "SomeMutation" => [
                                "None" => null,
                            ],
                        ],
                        "explicit_royalty_currencies" => [
                            "Some" => [],
                        ],
                    ],
                ],
            ],
        ],
        "topics" => [],
    ]
*/
