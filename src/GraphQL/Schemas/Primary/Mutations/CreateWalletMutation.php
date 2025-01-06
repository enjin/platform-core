<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Mutations;

use Closure;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\InPrimarySchema;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Services\Database\WalletService;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class CreateWalletMutation extends Mutation implements PlatformGraphQlMutation
{
    use InPrimarySchema;

    /**
     * Get the mutation's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'CreateWallet',
            'description' => __('enjin-platform::mutation.create_wallet.description'),
        ];
    }

    /**
     * Get the mutation's return type.
     */
    public function type(): Type
    {
        return GraphQL::type('Boolean!');
    }

    /**
     * Get the mutation's arguments definition.
     */
    #[\Override]
    public function args(): array
    {
        return [
            'externalId' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::mutation.create_wallet.args.externalId'),
                'rules' => ['unique:wallets,external_id'],
            ],
        ];
    }

    /**
     * Resolve the mutation's request.
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields, WalletService $walletService): mixed
    {
        return $walletService->store([
            'public_key' => null,
            'external_id' => $args['externalId'],
            'managed' => true,
        ]);
    }
}
