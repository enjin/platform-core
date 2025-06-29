<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Arr;
use Enjin\Platform\GraphQL\Schemas\FuelTanks\Traits\InFuelTanksSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Models\Indexer\FuelTank;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;

class FuelTankType extends Type implements PlatformGraphQlType
{
    use InFuelTanksSchema;

    /**
     * Get the type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'FuelTank',
            'description' => __('enjin-platform::type.fuel_tank.description'),
            'model' => FuelTank::class,
        ];
    }

    /**
     * Get the type's fields.
     */
    #[\Override]
    public function fields(): array
    {
        return [
            // Properties
            'id' => [
                'type' => GraphQL::type('String!'),
                'description' => '',
            ],
            'tankId' => [
                'type' => GraphQL::type('Account'),
                'description' => __('enjin-platform::mutation.fuel_tank.args.tankId'),
                'deprecationReason' => '',
            ],
            'name' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::type.fuel_tank.field.name'),
            ],
            'reservesAccountCreationDeposit' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::type.fuel_tank.field.reservesAccountCreationDeposit'),
                'alias' => 'user_account_management',
                'is_relation' => false,
                'resolve' => fn ($t) => Arr::get($t->user_account_management, 'tankReservesAccountCreationDeposit'),
            ],
            //            'coveragePolicy' => [
            //                'type' => GraphQL::type('CoveragePolicy!'),
            //                'description' => __('enjin-platform::type.fuel_tank.field.coveragePolicy'),
            //                'alias' => 'coverage_policy',
            //            ],
            'isFrozen' => [
                'type' => GraphQL::type('Boolean!'),
                'description' => __('enjin-platform::type.fuel_tank.field.isFrozen'),
                'alias' => 'is_frozen',
            ],
            'accountCount' => [
                'type' => GraphQL::type('Int!'),
                'description' => __('enjin-platform::type.fuel_tank.field.accountCount'),
                'alias' => 'account_count',
            ],
            'owner' => [
                'type' => GraphQL::type('Wallet!'),
                'description' => __('enjin-platform::type.fuel_tank.field.wallet'),
            ],
            'accounts' => [
                'type' => GraphQL::type('[Wallet]'),
                'description' => __('enjin-platform::type.fuel_tank.field.accounts'),
                'is_relation' => true,
            ],
            'accountRules' => [
                'type' => GraphQL::type('[AccountRule]'),
                'description' => __('enjin-platform::type.fuel_tank.field.accountRules'),
            ],
            //            'dispatchRules' => [
            //                'type' => GraphQL::type('[DispatchRule]'),
            //                'description' => __('enjin-platform::type.fuel_tank.field.dispatchRules'),
            //                'is_relation' => true,
            //            ],

            // Deprecated
            'reservesExistentialDeposit' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::type.fuel_tank.field.reservesExistentialDeposit'),
                'deprecationReason' => __('enjin-platform::deprecated.fuel_tank.field.reservesExistentialDeposit'),
                'alias' => 'user_account_management',
                'is_relation' => false,
                'resolve' => fn ($t) => Arr::get($t->user_account_management, 'tankReservesExistentialDeposit'),
            ],
            'providesDeposit' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::type.fuel_tank.field.providesDeposit'),
                'deprecationReason' => __('enjin-platform::deprecated.fuel_tank.field.providesDeposit'),
                'alias' => 'provides_deposit',
            ],
        ];
    }
}
