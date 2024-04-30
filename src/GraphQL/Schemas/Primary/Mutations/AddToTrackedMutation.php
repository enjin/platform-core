<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Mutations;

use Closure;
use Enjin\Platform\Enums\Global\ModelType;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\InPrimarySchema;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Models\Syncable;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Services\Database\CollectionService;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class AddToTrackedMutation extends Mutation implements PlatformGraphQlMutation
{
    use InPrimarySchema;

    /**
     * Get the mutation's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'AddToTracked',
            'description' => __('enjin-platform::mutation.add_to_tracked.description'),
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
            'hotSync' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::mutation.add_to_tracked.args.hot_sync'),
                'defaultValue' => true,
            ],
        ];
    }

    /**
     * Resolve the mutation's request.
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields, CollectionService $collectionService): mixed
    {
        collect($args['chainIds'])->each(function ($id) use ($args, $collectionService) {
            Syncable::query()->updateOrInsert(
                ['syncable_id' => $id, 'syncable_type' => ModelType::getEnumCase($args['type'])->value],
                ['syncable_id' => $id, 'syncable_type' => ModelType::getEnumCase($args['type'])->value],
            );

            if ($args['hotSync']) {
                switch (ModelType::getEnumCase($args['type'])) {
                    case ModelType::COLLECTION:
                        $collectionService->hotSync($id);

                        break;
                    default:
                        break;
                }
            }
        });

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
