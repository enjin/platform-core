<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasTokenIdFields;
use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

class SimpleTransferParamsInputType extends InputType implements PlatformGraphQlType
{
    use HasTokenIdFields;
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'SimpleTransferParams',
            'description' => __('enjin-platform::input_type.simple_transfer_params.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    #[\Override]
    public function fields(): array
    {
        return [
            ...$this->getTokenFields(__('enjin-platform::args.common.tokenId')),
            'amount' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::mutation.batch_set_attribute.args.amount'),
            ],
            'keepAlive' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::mutation.simple_transfer_token.args.params.keepAlive'),
                'deprecationReason' => __('enjin-platform::deprecated.simple_transfer_token.args.params.keepAlive'),
                'defaultValue' => false,
            ],
        ];
    }
}
