<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Enjin\Platform\GraphQL\Schemas\FuelTanks\Traits\InFuelTanksSchema;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;
use Enjin\Platform\Interfaces\PlatformGraphQlType;

class DispatchRuleInputType extends InputType implements PlatformGraphQlType
{
    use InFuelTanksSchema;

    /**
     * Get the input type's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'DispatchRuleInputType',
            'description' => __('enjin-platform::input_type.dispatch_rule.description'),
        ];
    }

    /**
     * Get the input type's fields.
     */
    #[Override]
    public function fields(): array
    {
        return [
            'whitelistedCallers' => [
                'type' => GraphQL::type('[String!]'),
                'description' => __('enjin-platform::input_type.dispatch_rule.field.whitelistedCallers'),
            ],
            'requireToken' => [
                'type' => GraphQL::type('MultiTokenIdInput'),
                'description' => __('enjin-platform::input_type.dispatch_rule.field.requireToken'),
            ],
            'whitelistedCollections' => [
                'type' => GraphQL::type('[BigInt!]'),
                'description' => __('enjin-platform::input_type.dispatch_rule.field.whitelistedCollections'),
            ],
            'maxFuelBurnPerTransaction' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform::input_type.dispatch_rule.field.maxFuelBurnPerTransaction'),
            ],
            'userFuelBudget' => [
                'type' => GraphQL::type('FuelBudgetInputType'),
                'description' => __('enjin-platform::input_type.dispatch_rule.field.userFuelBudget'),
            ],
            'tankFuelBudget' => [
                'type' => GraphQL::type('FuelBudgetInputType'),
                'description' => __('enjin-platform::input_type.dispatch_rule.field.tankFuelBudget'),
            ],
            'permittedExtrinsics' => [
                'type' => GraphQL::type('[TransactionMethod!]'),
                'description' => __('enjin-platform::input_type.permitted_extrinsics.description'),
            ],
            'requireSignature' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::input_type.require_signature.description'),
            ],
        ];
    }
}
