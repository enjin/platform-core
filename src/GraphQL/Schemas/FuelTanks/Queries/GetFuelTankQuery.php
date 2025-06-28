<?php

namespace Enjin\Platform\GraphQL\Schemas\FuelTanks\Queries;

use Closure;
use Enjin\Platform\GraphQL\Middleware\SingleFilterOnly;
use Enjin\Platform\Models\FuelTank;
use Enjin\Platform\Rules\ValidSubstrateAddress;
use Enjin\Platform\Support\SS58Address;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Builder;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;

class GetFuelTankQuery extends FuelTanksQuery
{
    protected $middleware = [
        SingleFilterOnly::class,
    ];

    /**
     * Get the mutation's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'GetFuelTank',
            'description' => __('enjin-platform::query.get_fuel_tank.description'),
        ];
    }

    /**
     * Get the query's return type.
     */
    public function type(): Type
    {
        return GraphQL::type('FuelTank');
    }

    /**
     * Get the mutation's arguments definition.
     */
    #[Override]
    public function args(): array
    {
        return [
            'id' => [
                'type' => GraphQL::type('String'),
                'description' => '',
                'singleFilter' => true,
            ],
            'tankId' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::mutation.fuel_tank.args.tankId'),
                'deprecationReason' => '',
                'singleFilter' => true,
            ],
            'name' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::type.fuel_tank.field.name'),
                'singleFilter' => true,
            ],
        ];
    }

    /**
     * Resolve the mutation's request.
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        return FuelTank::selectFields($getSelectFields)
            ->when(!empty($args['id']), fn (Builder $query) => $query->where('id', SS58Address::getPublicKey($args['id'])))
            ->when(!empty($args['tankId']), fn (Builder $query) => $query->where('id', SS58Address::getPublicKey($args['tankId'])))
            ->when(!empty($args['name']), fn (Builder $query) => $query->where('name', $args['name']))
            ->first();
    }

    /**
     * Get the mutation's request validation rules.
     */
    #[Override]
    protected function rules(array $args = []): array
    {
        return [
            'id' => ['nullable', new ValidSubstrateAddress()],
            'tankId' => ['nullable', new ValidSubstrateAddress()],
            'name' => ['nullable', 'max:32'],
        ];
    }
}
