<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Queries;

use Closure;
use Enjin\Platform\GraphQL\Middleware\ResolvePage;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\InPrimarySchema;
use Enjin\Platform\GraphQL\Types\Pagination\ConnectionInput;
use Enjin\Platform\Interfaces\PlatformGraphQlQuery;
use Enjin\Platform\Models\PendingEvent;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class GetPendingEventsQuery extends Query implements PlatformGraphQlQuery
{
    use InPrimarySchema;

    protected $middleware = [
        ResolvePage::class,
    ];

    /**
     * Get the query's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'GetPendingEvents',
            'description' => __('enjin-platform::query.get_pending_events.description'),
        ];
    }

    /**
     * Get the query's return type.
     */
    public function type(): Type
    {
        return GraphQL::paginate('PendingEvent', 'PendingEventConnection');
    }

    /**
     * Get the query's arguments definition.
     */
    public function args(): array
    {
        return ConnectionInput::args([
            'acknowledgeEvents' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::query.get_pending_events.args.acknowledgeEvents'),
                'defaultValue' => false,
            ],
        ]);
    }

    /**
     * Resolve the query's request.
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): mixed
    {
        $events = PendingEvent::loadSelectFields($resolveInfo, $this->name)
            ->cursorPaginateWithTotal('id', $args['first']);

        if ($args['acknowledgeEvents'] === true) {
            $eventsToClean = $events['items']->getCollection()->pluck('id')->toArray();
            PendingEvent::query()
                ->whereIn('id', $eventsToClean)
                ->get()
                ->each(fn ($pendingEvent) => $pendingEvent->delete());
        }

        return $events;
    }
}
