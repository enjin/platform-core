<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Support\Hex;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

class MintPolicyInputType extends InputType implements PlatformGraphQlType
{
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'MintPolicy',
            'description' => __('enjin-platform::input_type.mint_policy.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    #[Override]
    public function fields(): array
    {
        return [
            'maxTokenCount' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform::type.collection_type.field.maxTokenCount'),
                'rules' => ['nullable', new MinBigInt(1), new MaxBigInt(Hex::MAX_UINT64)],
            ],
            'maxTokenSupply' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform::type.collection_type.field.maxTokenSupply'),
                'rules' => ['nullable', new MinBigInt(1), new MaxBigInt(Hex::MAX_UINT128)],
            ],
            'forceCollapsingSupply' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::input_type.mint_policy.field.forceCollapsingSupply'),
                'defaultValue' => false,
            ],
            // Deprecated
            'forceSingleMint' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::input_type.mint_policy.field.forceSingleMint'),
                'deprecationReason' => __('enjin-platform::deprecated.mint_policy.field.forceSingleMint'),
                'defaultValue' => false,
            ],
        ];
    }
}
