<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\MultiTokens;

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
    public readonly ?bool $listingForbidden;
    public readonly string $behaviorMutation;
    public readonly bool $isCurrency;
    public readonly ?string $beneficiary;
    public readonly ?string $percentage;

    public static function fromChain(array $data): self
    {
        $self = new self();

        $self->extrinsicIndex = Arr::get($data, 'phase.ApplyExtrinsic');
        $self->module = array_key_first(Arr::get($data, 'event'));
        $self->name = array_key_first(Arr::get($data, 'event.' . $self->module));
        $self->collectionId = $self->getValue($data, ['collection_id', 'T::CollectionId']);
        $self->tokenId = $self->getValue($data, ['token_id', 'T::TokenId']);
        $self->listingForbidden = $self->getValue($data, ['mutation.listing_forbidden.SomeMutation', 'T::TokenMutation.listing_forbidden.SomeMutation']);
        $self->behaviorMutation = is_string($behavior = $self->getValue($data, ['mutation.behavior', 'T::TokenMutation.behavior'])) ? $behavior : array_key_first($behavior);
        $self->isCurrency = $self->getValue($data, ['mutation.behavior.SomeMutation.Some', 'T::TokenMutation.behavior.SomeMutation.Some']) === 'IsCurrency';
        $self->beneficiary = Account::parseAccount($self->getValue($data, ['mutation.behavior.SomeMutation.Some.HasRoyalty.beneficiary', 'T::TokenMutation.behavior.SomeMutation.HasRoyalty.beneficiary']));
        $self->percentage = $self->getValue($data, ['mutation.behavior.SomeMutation.Some.HasRoyalty.percentage', 'T::TokenMutation.behavior.SomeMutation.HasRoyalty.percentage']);

        if ($self->getValue($data, ['T::TokenMutation.metadata.SomeMutation']) != null) {
            throw new \Exception('Metadata is not null');
        }

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
            ['type' => 'listing_forbidden', 'value' => $this->listingForbidden],
            ['type' => 'behavior_mutation', 'value' => $this->behaviorMutation],
            ['type' => 'is_currency', 'value' => $this->isCurrency],
            ['type' => 'beneficiary', 'value' => $this->beneficiary],
            ['type' => 'percentage', 'value' => $this->percentage],
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
                "TokenMutated" => [
                    "collection_id" => "10685",
                    "token_id" => "1",
                    "mutation" => [
                        "behavior" => [
                            "SomeMutation" => [
                                "Some" => [
                                    "HasRoyalty" => [
                                        "beneficiary" => "d43593c715fdd31c61141abd04a99fd6822c8558854ccde39a5684e7a56da27d",
                                        "percentage" => 10000000,
                                    ],
                                ],
                            ],
                        ],
                        "listing_forbidden" => [
                            "SomeMutation" => true,
                        ],
                        "metadata" => "NoMutation",
                    ],
                ],
            ],
        ],
        "topics" => [],
    ]
 */
