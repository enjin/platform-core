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

class TransferBalanceParamsInputType extends InputType implements PlatformGraphQlType
{
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'TransferBalanceParams',
            'description' => __('enjin-platform::input_type.balance_transfer.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    #[Override]
    public function fields(): array
    {
        return [
            'value' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::mutation.batch_set_attribute.args.amount'),
                'rules' => [new MinBigInt(1), new MaxBigInt(Hex::MAX_UINT128)],
            ],
            'keepAlive' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::mutation.batch_set_attribute.args.keepAlive'),
                'defaultValue' => false,
            ],
        ];
    }
}
