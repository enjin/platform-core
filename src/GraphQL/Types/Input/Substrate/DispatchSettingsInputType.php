<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Rebing\GraphQL\Support\Facades\GraphQL;

class DispatchSettingsInputType extends InputType
{
    /**
     * Get the input type's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'DispatchSettingsInputType',
            'description' => __('enjin-platform-fuel-tanks::input_type.dispatch_settings.description'),
        ];
    }

    /**
     * Get the input type's fields.
     */
    public function fields(): array
    {
        return [
            'paysRemainingFee' => [
                'type' => GraphQL::type('Boolean!'),
                'description' => __('enjin-platform-fuel-tanks::input_type.dispatch_settings.field.paysRemainingFee'),
            ],
            'signature' => [
                'type' => GraphQL::type('ExpirableSignatureInputType'),
                'description' => __('enjin-platform-fuel-tanks::input_type.dispatch_settings.field.signature'),
            ],
        ];
    }
}
