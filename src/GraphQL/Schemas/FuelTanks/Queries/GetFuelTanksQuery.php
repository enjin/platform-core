<?php

namespace Enjin\Platform\GraphQL\Schemas\FuelTanks\Queries;

use Closure;
use Enjin\Platform\Models\FuelTank;
use Enjin\Platform\GraphQL\Middleware\ResolvePage;
use Enjin\Platform\GraphQL\Types\Pagination\ConnectionInput;
use Enjin\Platform\Rules\ValidSubstrateAddress;
use Enjin\Platform\Support\SS58Address;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Rebing\GraphQL\Support\Facades\GraphQL;

class GetFuelTanksQuery extends FuelTanksQuery
{
    protected $middleware = [
        ResolvePage::class,
    ];

    /**
     * Get the mutation's attributes.
     */
    #[\Override]
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
    #[\Override]
    public function args(): array
    {
        return ConnectionInput::args([
            'names' => [
                'type' => GraphQL::type('[String!]'),
                'description' => __('enjin-platform::type.fuel_tank.field.name'),
            ],
            'tankIds' => [
                'type' => GraphQL::type('[String!]'),
                'description' => __('enjin-platform::mutation.fuel_tank.args.tankId'),
            ],
        ]);
    }

    /**
     * Resolve the mutation's request.
     */
    public function resolve(
        $root,
        array $args,
        $context,
        ResolveInfo $resolveInfo,
        Closure $getSelectFields
    ) {
        return FuelTank::loadSelectFields($resolveInfo, $this->name)
            ->when(
                $publicKeys = Arr::get($args, 'tankIds'),
                fn ($query) => $query->whereIn(
                    'public_key',
                    collect($publicKeys)->map(fn ($publicKey) => SS58Address::getPublicKey($publicKey))
                )
            )->when(
                $names = Arr::get($args, 'names'),
                fn ($query) => $query->whereIn('name', $names)
            )->cursorPaginateWithTotalDesc('id', $args['first']);
    }

    /**
     * Get the mutation's request validation rules.
     */
    #[\Override]
    protected function rules(array $args = []): array
    {
        return [
            'names' => [
                'bail',
                'prohibits:tankIds',
                'array',
            ],
            'names.*' => ['bail', 'filled', 'max:32', 'distinct'],
            'tankIds' => [
                'bail',
                'prohibits:names',
                'array',
            ],
            'tankIds.*' => ['bail', 'filled', 'distinct', new ValidSubstrateAddress()],
        ];
    }
}
