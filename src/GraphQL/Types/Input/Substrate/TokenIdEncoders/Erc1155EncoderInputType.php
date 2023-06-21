<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate\TokenIdEncoders;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Rules\ValidHex;
use Enjin\Platform\Support\Hex;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

class Erc1155EncoderInputType extends InputType implements PlatformGraphQlType
{
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'Erc1155EncoderInput',
            'description' => __('erc1155_encoder.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    public function fields(): array
    {
        return [
            'tokenId' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::input_type.token_id_encoder.erc1155.token_id.description'),
                'rules' => ['required', new ValidHex(8)],
            ],
            'index' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform::input_type.token_id_encoder.erc1155.index.description'),
                'rules' => ['sometimes', new MinBigInt(), new MaxBigInt(Hex::MAX_UINT64)],
            ],
        ];
    }
}
