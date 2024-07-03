<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Queries;

use Closure;
use Enjin\Platform\Enums\Global\FilterType;
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
            'channelFilters' => [
                'type' => GraphQL::type('[StringFilter!]'),
                'description' => __('enjin-platform::query.get_pending_events.args.channelFilter'),
                'defaultValue' => [],
            ],
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
        $events = PendingEvent::loadSelectFields($resolveInfo, $this->name);
        $filteredEvents = $events->when($args['channelFilters'] ?? false, function ($query) use ($args): void {
            $query->where(function ($query) use ($args): void {
                $orFilters = collect($args['channelFilters'])->where('type', FilterType::OR->value)->all();
                $andFilters = collect($args['channelFilters'])->where('type', FilterType::AND->value)->all();

                foreach ($andFilters as $filter) {
                    $query->whereJsonContains('channels', $filter['filter']);
                }

                if (count($orFilters) > 0) {
                    $query->where(function ($query) use ($orFilters): void {
                        foreach ($orFilters as $filter) {
                            $query->orWhereJsonContains('channels', $filter['filter']);
                        }
                    });
                }
            });
        })->cursorPaginateWithTotal('id', $args['first']);

        if ($args['acknowledgeEvents'] === true) {
            $filteredEvents['items']->getCollection()->each->delete();
        }

        return $filteredEvents;
    }
}
