<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Enjin\Platform\GraphQL\Schemas\FuelTanks\Traits\InFuelTanksSchema;
use Rebing\GraphQL\Support\InputType;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Rebing\GraphQL\Support\Facades\GraphQL;

class ExpirableSignatureInputType extends InputType implements PlatformGraphQlType
{
    use InFuelTanksSchema;

    /**
     * Get the input type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'ExpirableSignatureInputType',
            'description' => __('enjin-platform::input_type.expirable_signature.description'),
        ];
    }

    /**
     * Get the input type's fields.
     */
    #[\Override]
    public function fields(): array
    {
        return [
            'signature' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::input_type.expirable_signature.field.signature'),
            ],
            'expiryBlock' => [
                'type' => GraphQL::type('Int!'),
                'description' => __('enjin-platform::input_type.expirable_signature.field.expiryBlock'),
            ],
        ];
    }
}
