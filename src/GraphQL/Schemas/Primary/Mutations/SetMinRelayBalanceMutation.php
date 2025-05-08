<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Mutations;

use Closure;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\InPrimarySchema;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Support\Hex;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class SetMinRelayBalanceMutation extends Mutation implements PlatformGraphQlMutation
{
    use InPrimarySchema;

    /**
     * Get the mutation's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'SetMinRelayBalance',
            'description' => __('enjin-platform::mutation.set_min_relay_balance.description'),
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
            'amount' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::query.set_min_relay_balance.args.amount'),
            ],
        ];
    }

    /**
     * Resolve the mutation's request.
     */
    public function resolve(
        $root,
        array $args,
        $context,
        ResolveInfo $resolveInfo,
        Closure $getSelectFields,
    ): mixed {
        Wallet::where('managed', true)->update([
            'min_relay_balance' =>  $args['amount'],
        ]);

        return true;
    }

    /**
     * Get the validation rules.
     */
    #[\Override]
    protected function rules(array $args = []): array
    {
        return [
            'amount' =>  [
                'bail',
                'filled',
                new MinBigInt(0),
                new MaxBigInt(Hex::MAX_UINT128),
            ],
        ];
    }
}
