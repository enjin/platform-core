<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Rebing\GraphQL\Support\Facades\GraphQL;

class ExpirableSignatureInputType extends InputType
{
    /**
     * Get the input type's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'ExpirableSignatureInputType',
            'description' => __('enjin-platform-fuel-tanks::input_type.expirable_signature.description'),
        ];
    }

    /**
     * Get the input type's fields.
     */
    public function fields(): array
    {
        return [
            'signature' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform-fuel-tanks::input_type.expirable_signature.field.signature'),
            ],
            'expiryBlock' => [
                'type' => GraphQL::type('Int!'),
                'description' => __('enjin-platform-fuel-tanks::input_type.expirable_signature.field.expiryBlock'),
            ],
        ];
    }
}
