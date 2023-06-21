<?php

namespace Enjin\Platform\GraphQL\Types\Global;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Support\JSON;
use Enjin\Platform\Traits\HasSelectFields;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class PendingEventType extends GraphQLType implements PlatformGraphQlType
{
    use InSubstrateSchema;
    use HasSelectFields;

    /**
     * Get the type's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'PendingEvent',
            'description' => __('enjin-platform::type.pending_event.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    public function fields(): array
    {
        return [
            'id' => [
                'type' => GraphQL::type('Int!'),
                'description' => __('enjin-platform::type.pending_event.field.id'),
            ],
            'uuid' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::type.pending_event.field.uuid'),
            ],
            'name' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::type.pending_event.field.name'),
            ],
            'sent' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::type.pending_event.field.sent'),
            ],
            'channels' => [
                'type' => GraphQL::type('[String]'),
                'description' => __('enjin-platform::type.pending_event.field.channels'),
                'resolve' => fn ($event) => JSON::decode($event->channels),
            ],
            'data' => [
                'type' => GraphQL::type('Json!'),
                'description' => __('enjin-platform::type.pending_event.field.data'),
                'resolve' => fn ($event) => JSON::decode($event->data),
            ],
        ];
    }
}
