<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Support\Hex;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

class TokenFilterInputType extends InputType implements PlatformGraphQlType
{
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'TokenFilterInput',
            'description' => __('enjin-platform::input_type.multi_token_id.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    #[\Override]
    public function fields(): array
    {
        return [
            'collectionId' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::input_type.multi_token_id.field.collectionId'),
                'rules' => [new MinBigInt(), new MaxBigInt(Hex::MAX_UINT128)],
            ],
            'tokenIds' => [
                'type' => GraphQL::type('[IntegerRangeString!]!'),
                'description' => __('enjin-platform::args.tokenId'),
            ],
        ];
    }
}
