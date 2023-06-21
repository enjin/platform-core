<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Models\Event;
use Enjin\Platform\Support\JSON;
use Enjin\Platform\Traits\HasSelectFields;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class EventType extends GraphQLType implements PlatformGraphQlType
{
    use HasSelectFields;
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'Event',
            'description' => __('enjin-platform::type.event.description'),
            'model' => Event::class,
        ];
    }

    /**
     * Get the type's fields definition.
     */
    public function fields(): array
    {
        return [
            'phase' => [
                'type' => GraphQL::type('Int!'),
                'description' => __('enjin-platform::type.event.field.phase'),
            ],
            'lookUp' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::type.event.field.lookUp'),
                'alias' => 'look_up',
            ],
            'moduleId' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::type.event.field.moduleId'),
                'alias' => 'module_id',
            ],
            'eventId' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::type.event.field.eventId'),
                'alias' => 'event_id',
            ],
            'params' => [
                'type' => GraphQL::type('[EventParam]'),
                'description' => __('enjin-platform::type.event.field.params'),
                'resolve' => function ($event) {
                    return JSON::decode($event['params']);
                },
            ],
        ];
    }
}
