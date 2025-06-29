<?php

namespace Enjin\Platform\GraphQL\Schemas\FuelTanks\Queries;

use Closure;
use Enjin\Platform\GraphQL\Middleware\ResolvePage;
use Enjin\Platform\GraphQL\Middleware\SingleFilterOnly;
use Enjin\Platform\GraphQL\Types\Pagination\ConnectionInput;
use Enjin\Platform\Rules\ValidSubstrateAddress;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;

class GetAccountsQuery extends FuelTanksQuery
{
    protected $middleware = [
        ResolvePage::class,
        SingleFilterOnly::class,
    ];

    /**
     * Get the mutation's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'GetAccounts',
            'description' => __('enjin-platform::query.get_fuel_accounts.description'),
        ];
    }

    /**
     * Get the query's return type.
     */
    public function type(): Type
    {
        return GraphQL::paginate('Wallet', 'WalletConnection');
    }

    /**
     * Get the mutation's arguments definition.
     */
    #[Override]
    public function args(): array
    {
        return ConnectionInput::args([
            'id' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::mutation.fuel_tank.args.tankId'),
                'singleFilter' => true,
            ],
            'tankId' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::mutation.fuel_tank.args.tankId'),
                'singleFilter' => true,
            ],
        ]);
    }

    /**
     * Resolve the mutation's request.
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
//        return Wallet::selectFields($getSelectFields)
//            ->whereHas('fuelTanks', fn ($query) => $query->where('public_key', SS58Address::getPublicKey($args['tankId'])))
//            ->cursorPaginateWithTotalDesc('id', $args['first']);
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
        ];
    }
}
