<?php

namespace Enjin\Platform\GraphQL\Schemas\FuelTanks\Queries;

use Closure;
use Enjin\Platform\GraphQL\Middleware\ResolvePage;
use Enjin\Platform\GraphQL\Middleware\SingleFilterOnly;
use Enjin\Platform\GraphQL\Types\Pagination\ConnectionInput;
use Enjin\Platform\Models\FuelTank;
use Enjin\Platform\Support\SS58Address;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;

class GetFuelTanksQuery extends FuelTanksQuery
{
    protected $middleware = [
        SingleFilterOnly::class,
        ResolvePage::class,
    ];

    /**
     * Get the mutation's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'GetFuelTanks',
            'description' => __('enjin-platform::query.get_fuel_tanks.description'),
        ];
    }

    /**
     * Get the query's return type.
     */
    public function type(): Type
    {
        return GraphQL::paginate('FuelTank', 'FuelTankConnection');
    }

    /**
     * Get the mutation's arguments definition.
     */
    #[Override]
    public function args(): array
    {
        return ConnectionInput::args([
            'ids' => [
                'type' => GraphQL::type('[String]'),
                'description' => __('enjin-platform::mutation.fuel_tank.args.tankId'),
                'singleFilter' => true,
            ],
            'tankIds' => [
                'type' => GraphQL::type('[String]'),
                'description' => __('enjin-platform::mutation.fuel_tank.args.tankId'),
                'singleFilter' => true,
            ],
            'names' => [
                'type' => GraphQL::type('[String]'),
                'description' => __('enjin-platform::type.fuel_tank.field.name'),
                'singleFilter' => true,
            ],
        ]);
    }

    /**
     * Resolve the mutation's request.
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        return FuelTank::selectFields($getSelectFields)
            ->when(
                !empty($args['ids']),
                fn ($query) => $query->whereIn(
                    'id',
                    collect($args['ids'])->map(fn ($publicKey) => SS58Address::getPublicKey($publicKey))
                )
            )
            ->when(
                !empty($args['tankIds']),
                fn ($query) => $query->whereIn(
                    'id',
                    collect($args['tankIds'])->map(fn ($publicKey) => SS58Address::getPublicKey($publicKey))
                )
            )
            ->when(!empty($args['names']), fn ($query) => $query->whereIn('name', $args['names']))
            ->cursorPaginateWithTotalDesc('id', $args['first']);
    }
}
