<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Mutations;

use Closure;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\InPrimarySchema;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Models\Laravel\PendingEvent;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class AcknowledgeEventsMutation extends Mutation implements PlatformGraphQlMutation
{
    use InPrimarySchema;

    /**
     * Get the mutation's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'AcknowledgeEvents',
            'description' => __('enjin-platform::mutation.acknowledge_events.description'),
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
            'uuids' => [
                'type' => GraphQL::type('[String!]!'),
                'description' => __('enjin-platform::mutation.acknowledge_events.args.uuids'),
            ],
        ];
    }

    /**
     * Resolve the mutation's request.
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): mixed
    {
        PendingEvent::query()
            ->whereIn('uuid', $args['uuids'])
            ->get()
            ->each(fn ($pendingEvent) => $pendingEvent->delete());

        return true;
    }

    /**
     * Get the validation rules.
     */
    protected function rules(array $args = []): array
    {
        return [
            'uuids' => ['bail', 'array', 'min:1', 'max:1000', 'distinct'],
            'uuids.*' => ['filled'],
        ];
    }
}
