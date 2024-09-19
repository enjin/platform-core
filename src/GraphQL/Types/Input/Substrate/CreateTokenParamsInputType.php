<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Rules\DistinctAttributes;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Support\Hex;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

class CreateTokenParamsInputType extends InputType implements PlatformGraphQlType
{
    use InSubstrateSchema;
    use Traits\HasTokenIdFields;

    /**
     * Get the type's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'CreateTokenParams',
            'description' => __('enjin-platform::input_type.create_token_params.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    public function fields(): array
    {
        return [
            ...$this->getTokenFields(),
            'initialSupply' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform::input_type.create_token_params.field.initialSupply'),
                'defaultValue' => 1,
                'rules' => [new MinBigInt(0), new MaxBigInt(Hex::MAX_UINT128)],
            ],
            'accountDepositCount' => [
                'type' => GraphQL::type('Int'),
                'description' => __('enjin-platform::input_type.create_token_params.field.accountDepositCount'),
                'defaultValue' => 0,
                'rules' => ['bail', 'nullable', 'integer', 'min:0', 'max:4294967295'],
            ],
            'cap' => [
                'type' => GraphQL::type('TokenMintCap'),
                'description' => __('enjin-platform::input_type.create_token_params.field.cap'),
                'defaultValue' => null,
            ],
            'behavior' => [
                'type' => GraphQL::type('TokenMarketBehaviorInput'),
                'description' => __('enjin-platform::input_type.token_market_behavior.description'),
                'defaultValue' => null,
            ],
            'listingForbidden' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::input_type.create_token_params.field.listingForbidden'),
                'defaultValue' => false,
            ],
            'freezeState' => [
                'type' => GraphQL::type('FreezeStateType'),
                'description' => __('enjin-platform::input_type.token_freeze_state.description'),
                'defaultValue' => null,
            ],
            'attributes' => [
                'type' => GraphQL::type('[AttributeInput]'),
                'description' => __('enjin-platform::input_type.create_token_params.field.attributes'),
                'defaultValue' => [],
                'rules' => ['bail', 'nullable', 'array', 'min:0', 'max:10', new DistinctAttributes()],
            ],
            'infusion' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform::input_type.create_token_params.field.infusion'),
                'defaultValue' => 0,
                'rules' => [new MinBigInt(0), new MaxBigInt(Hex::MAX_UINT128)],
            ],
            'anyoneCanInfuse' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::input_type.create_token_params.field.anyoneCanInfuse'),
                'defaultValue' => false,
            ],
            'metadata' => [
                'type' => GraphQL::type('MetadataInput'),
                'description' => __('enjin-platform::input_type.create_token_params.field.metadata'),
                'defaultValue' => null,
            ],


            // Deprecated
            'unitPrice' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform::input_type.create_token_params.field.unitPrice'),
                'deprecationReason' => __('enjin-platform::deprecated.create_token_params.field.unitPrice'),
                'defaultValue' => null,
            ],
        ];
    }
}
