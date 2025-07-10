<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Queries;

use Closure;
use Enjin\Platform\GraphQL\Middleware\ResolvePage;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Types\Pagination\ConnectionInput;
use Enjin\Platform\Interfaces\PlatformGraphQlQuery;
use Enjin\Platform\Models\Indexer\Account;
use Enjin\Platform\Services\Blockchain\Interfaces\BlockchainServiceInterface;
use Enjin\Platform\Support\SS58Address;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Builder;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class GetWalletsQuery extends Query implements PlatformGraphQlQuery
{
    use InPrimarySubstrateSchema;

    protected $middleware = [
        ResolvePage::class,
    ];

    /**
     * Get the query's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'GetWallets',
            'description' => __('enjin-platform::query.get_wallets.description'),
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
     * Get the query's arguments definition.
     */
    #[Override]
    public function args(): array
    {
        return ConnectionInput::args([
            'ids' => [
                'type' => GraphQL::type('[String]'),
                'description' => __('enjin-platform::query.get_wallet.args.id'),
            ],
            'accounts' => [
                'type' => GraphQL::type('[String]'),
                'description' => __('enjin-platform::query.get_wallet.args.account'),
            ],
            'externalIds' => [
                'type' => GraphQL::type('[String]'),
                'description' => __('enjin-platform::query.get_wallet.args.externalId'),
            ],
            'verificationIds' => [
                'type' => GraphQL::type('[String]'),
                'description' => __('enjin-platform::query.get_wallet.args.verificationId'),
            ],
            'managed' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::query.get_wallet.args.managed'),
                'defaultValue' => false,
            ],
        ]);
    }

    /**
     * Resolve the query's request.
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields, BlockchainServiceInterface $blockchainService): mixed
    {
        return Account::selectFields($getSelectFields)
            ->when(!empty($args['ids']), fn (Builder $query) => $query->whereIn('id', $args['ids']))
            // ->when($ids = Arr::get($args, 'ids'), fn (Builder $query) => $query->whereIn('id', $ids))
            // ->when($externalIds = Arr::get($args, 'externalIds'), fn (Builder $query) => $query->whereIn('external_id', $externalIds))
            // ->when($verificationIds = Arr::get($args, 'verificationIds'), fn (Builder $query) => $query->whereIn('verification_id', $verificationIds))
            // ->when($accounts = Arr::get($args, 'accounts'), fn (Builder $query) => $query->whereIn('public_key', collect($accounts)->map(fn ($val) => SS58Address::getPublicKey($val))->toArray()))
            // ->when($managed = Arr::get($args, 'managed'), fn (Builder $query) => $query->where('managed', $managed))
            ->when(!empty($args['account']), fn (Builder $query) => $query->where('id', SS58Address::getPublicKey($args['account'])))
            ->cursorPaginateWithTotal('id', $args['first']);
    }

    /**
     * Get the validation rules.
     */
    //    #[Override]
    //    protected function rules(array $args = []): array
    //    {
    //        return [
    //            'ids' => [
    //                Rule::prohibitedIf(!empty($args['verificationIds']) || !empty($args['externalIds']) || !empty($args['accounts'])),
    //                'array',
    //                'min:1',
    //                'max:100',
    //            ],
    //            'ids.*' => ['bail', new MinBigInt(), new MaxBigInt()],
    //            'externalIds' => [
    //                Rule::prohibitedIf(!empty($args['ids']) || !empty($args['verificationIds']) || !empty($args['accounts'])),
    //                'array',
    //                'min:1',
    //                'max:100',
    //            ],
    //            'externalIds.*' => ['bail', 'filled', 'max:1000'],
    //            'verificationIds' => [
    //                Rule::prohibitedIf(!empty($args['ids']) || !empty($args['externalIds']) || !empty($args['accounts'])),
    //                'array',
    //                'min:1',
    //                'max:100',
    //            ],
    //            'verificationIds.*' => ['bail', 'filled', 'max:1000', new ValidVerificationId()],
    //            'accounts' => [
    //                Rule::prohibitedIf(!empty($args['verificationIds']) || !empty($args['externalIds']) || !empty($args['ids'])),
    //                'array',
    //                'min:1',
    //                'max:100',
    //            ],
    //            'accounts.*' => ['bail', 'filled', 'max:255', new ValidSubstrateAccount()],
    //            'managed' => ['boolean'],
    //        ];
    //    }
}
