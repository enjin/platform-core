<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Enjin\Platform\GraphQL\Schemas\FuelTanks\Traits\InFuelTanksSchema;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;
use Enjin\Platform\Interfaces\PlatformGraphQlType;

class DispatchSettingsInputType extends InputType implements PlatformGraphQlType
{
    use InFuelTanksSchema;

    /**
     * Get the input type's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'DispatchSettingsInputType',
            'description' => __('enjin-platform::input_type.dispatch_settings.description'),
        ];
    }

    /**
     * Get the input type's fields.
     */
    #[Override]
    public function fields(): array
    {
        return [
            'paysRemainingFee' => [
                'type' => GraphQL::type('Boolean!'),
                'description' => __('enjin-platform::input_type.dispatch_settings.field.paysRemainingFee'),
            ],
            'signature' => [
                'type' => GraphQL::type('ExpirableSignatureInputType'),
                'description' => __('enjin-platform::input_type.dispatch_settings.field.signature'),
            ],
        ];
    }
}
