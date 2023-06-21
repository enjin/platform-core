<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Queries;

use Closure;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Types\Pagination\ConnectionInput;
use Enjin\Platform\Interfaces\PlatformGraphQlQuery;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Enjin\Platform\Rules\ValidVerificationId;
use Enjin\Platform\Services\Blockchain\Interfaces\BlockchainServiceInterface;
use Enjin\Platform\Support\SS58Address;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class GetWalletsQuery extends Query implements PlatformGraphQlQuery
{
    use InPrimarySubstrateSchema;

    /**
     * Get the query's attributes.
     */
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
    public function args(): array
    {
        return ConnectionInput::args([
            'ids' => [
                'type' => GraphQL::type('[Int!]'),
                'description' => __('enjin-platform::query.get_wallet.args.id'),
            ],
            'externalIds' => [
                'type' => GraphQL::type('[String!]'),
                'description' => __('enjin-platform::query.get_wallet.args.externalId'),
            ],
            'verificationIds' => [
                'type' => GraphQL::type('[String!]'),
                'description' => __('enjin-platform::query.get_wallet.args.verificationId'),
            ],
            'accounts' => [
                'type' => GraphQL::type('[String!]'),
                'description' => __('enjin-platform::query.get_wallet.args.account'),
            ],
        ]);
    }

    /**
     * Resolve the query's request.
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields, BlockchainServiceInterface $blockchainService): mixed
    {
        $wallets = Wallet::loadSelectFields($resolveInfo, $this->name)
            ->when($ids = Arr::get($args, 'ids'), fn (Builder $query) => $query->whereIn('id', $ids))
            ->when($externalIds = Arr::get($args, 'externalIds'), fn (Builder $query) => $query->whereIn('external_id', $externalIds))
            ->when($verificationIds = Arr::get($args, 'verificationIds'), fn (Builder $query) => $query->whereIn('verification_id', $verificationIds))
            ->when($accounts = Arr::get($args, 'accounts'), fn (Builder $query) => $query->whereIn('public_key', collect($accounts)->map(fn ($val) => SS58Address::getPublicKey($val))->toArray()))
            ->cursorPaginateWithTotalDesc('id', $args['first']);

        $fields = Arr::get($resolveInfo->lookAhead()->queryPlan(), 'edges.fields.node.fields', []);
        if ($wallets['total'] && (in_array('balance', $fields) || in_array('nonce', $fields))) {
            $wallets['items']->each(fn ($wallet) => $blockchainService->walletWithBalanceAndNonce($wallet));
        }

        return $wallets;
    }

    /**
     * Get the validatio rules.
     */
    protected function rules(array $args = []): array
    {
        return [
            'ids' => [
                Rule::prohibitedIf(!empty($args['verificationIds']) || !empty($args['externalIds']) || !empty($args['accounts'])),
                'array',
                'min:1',
                'max:100',
            ],
            'ids.*' => ['bail', new MinBigInt(), new MaxBigInt()],
            'externalIds' => [
                Rule::prohibitedIf(!empty($args['ids']) || !empty($args['verificationIds']) || !empty($args['accounts'])),
                'array',
                'min:1',
                'max:100',
            ],
            'externalIds.*' => ['bail', 'filled', 'max:1000'],
            'verificationIds' => [
                Rule::prohibitedIf(!empty($args['ids']) || !empty($args['externalIds']) || !empty($args['accounts'])),
                'array',
                'min:1',
                'max:100',
            ],
            'verificationIds.*' => ['bail', 'filled', new ValidVerificationId()],
            'verificationIds.*' => ['bail', 'filled', 'max:1000'],
            'accounts' => [
                Rule::prohibitedIf(!empty($args['verificationIds']) || !empty($args['externalIds']) || !empty($args['ids'])),
                'array',
                'min:1',
                'max:100',
            ],
            'accounts.*' => ['bail', 'filled', 'max:255', new ValidSubstrateAccount()],
        ];
    }
}
