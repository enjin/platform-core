<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Mutations;

use Closure;
use Enjin\Platform\Enums\Global\ModelType;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\InPrimarySchema;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Models\Syncable;
use Enjin\Platform\Rules\MinBigInt;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class RemoveFromTrackedMutation extends Mutation implements PlatformGraphQlMutation
{
    use InPrimarySchema;

    /**
     * Get the mutation's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'RemoveFromTracked',
            'description' => __('enjin-platform::mutation.remove_from_tracked.description'),
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
    public function args(): array
    {
        return [
            'type' => [
                'type' => GraphQL::type('ModelType!'),
                'description' => __('enjin-platform::mutation.add_to_tracked.args.model_type'),
            ],
            'chainIds' => [
                'type' => GraphQL::type('[BigInt!]!'),
                'description' => __('enjin-platform::mutation.add_to_tracked.args.chain_ids'),
            ],
        ];
    }

    /**
     * Resolve the mutation's request.
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): mixed
    {
        Syncable::query()->whereIn(
            'syncable_id',
            $args['chainIds']
        )->where(
            'syncable_type',
            ModelType::getEnumCase($args['type'])->value
        )->delete();

        return true;
    }

    /**
     * Get the validation rules.
     */
    protected function rules(array $args = []): array
    {
        return [
            'chainIds.*' => [new MinBigInt(2000)],
        ];
    }
}
