<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

class CollectionMutationInputType extends InputType implements PlatformGraphQlType
{
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'CollectionMutationInput',
            'description' => __('enjin-platform::input_type.collection_mutation.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    public function fields(): array
    {
        return [
            'owner' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::input_type.collection_mutation.field.owner'),
            ],
            'royalty' => [
                'type' => GraphQL::type('MutationRoyaltyInput'),
                'description' => __('enjin-platform::input_type.collection_mutation.field.royalty'),
            ],
            'explicitRoyaltyCurrencies' => [
                'type' => GraphQL::type('[MultiTokenIdInput]'),
                'description' => __('enjin-platform::mutation.create_collection.args.explicitRoyaltyCurrencies'),
            ],
        ];
    }
}
