<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Support\Hex;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

class MintTokenParamsInputType extends InputType implements PlatformGraphQlType
{
    use Traits\HasTokenIdFields;
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'MintTokenParams',
            'description' => __('enjin-platform::input_type.mint_token_params.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    public function fields(): array
    {
        return [
            ...$this->getTokenFields(),
            'amount' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::input_type.create_token_params.field.initialSupply'),
                'rules' => [new MinBigInt(1), new MaxBigInt(Hex::MAX_UINT128)],
            ],
            'unitPrice' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform::input_type.mint_token_params.field.unitPrice'),
                'rules' => ['nullable', new MinBigInt(1), new MaxBigInt(Hex::MAX_UINT128)],
            ],
        ];
    }
}
