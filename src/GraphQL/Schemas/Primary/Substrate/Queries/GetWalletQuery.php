<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Queries;

use Closure;
use Enjin\Platform\GraphQL\Middleware\SingleArgOnly;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlQuery;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Enjin\Platform\Rules\ValidVerificationId;
use Enjin\Platform\Services\Blockchain\Interfaces\BlockchainServiceInterface;
use Enjin\Platform\Support\SS58Address;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class GetWalletQuery extends Query implements PlatformGraphQlQuery
{
    use InPrimarySubstrateSchema;

    protected $middleware = [
        SingleArgOnly::class,
    ];

    /**
     * Get the query's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'GetWallet',
            'description' => __('enjin-platform::query.get_wallet.description'),
        ];
    }

    /**
     * Get the query's return type.
     */
    public function type(): Type
    {
        return GraphQL::type('Wallet');
    }

    /**
     * Get the query's arguments definition.
     */
    public function args(): array
    {
        return [
            'id' => [
                'type' => GraphQL::type('Int'),
                'description' => __('enjin-platform::query.get_wallet.args.id'),
                'rules' => ['nullable', 'filled'],
            ],
            'externalId' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::query.get_wallet.args.externalId'),
                'rules' => ['nullable', 'filled'],
            ],
            'verificationId' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::query.get_wallet.args.verificationId'),
                'rules' => ['bail', 'nullable', 'filled', new ValidVerificationId()],
            ],
            'account' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::query.get_wallet.args.account'),
                'rules' => ['bail', 'nullable', 'filled', new ValidSubstrateAccount()],
            ],
        ];
    }

    /**
     * Resolve the query's request.
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields, BlockchainServiceInterface $blockchainService): mixed
    {
        $wallet = Wallet::loadSelectFields($resolveInfo, $this->name)
            ->when(Arr::get($args, 'id'), fn (Builder $query) => $query->where('id', $args['id']))
            ->when(Arr::get($args, 'externalId'), fn (Builder $query) => $query->where('external_id', $args['externalId']))
            ->when(Arr::get($args, 'verificationId'), fn (Builder $query) => $query->where('verification_id', $args['verificationId']))
            ->when(Arr::get($args, 'account'), fn (Builder $query) => $query->where('public_key', SS58Address::getPublicKey($args['account'])))
            ->first();

        return $blockchainService->walletWithBalanceAndNonce($wallet ?? Arr::get($args, 'account'));
    }
}
