<?php

namespace Enjin\Platform\Marketplace\GraphQL\Types\Input;

use Enjin\Platform\Marketplace\GraphQL\Types\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class OfferParamsInputType extends InputType
{
    /**
     * Get the type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'OfferParamsInput',
            'description' => __('enjin-platform-marketplace::type.offer_data.description'),
        ];
    }

    /**
     * Get the type's fields.
     */
    #[\Override]
    public function fields(): array
    {
        return [
            'expiration' => [
                'type' => GraphQL::type('Int'),
                'description' => __('enjin-platform-marketplace::type.offer_data.field.expiration'),
            ],
        ];
    }
}
