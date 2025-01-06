<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Rules\ValidRoyaltyPercentage;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

class RoyaltyInputType extends InputType implements PlatformGraphQlType
{
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'RoyaltyInput',
            'description' => __('enjin-platform::input_type.mutation_royalty.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    #[\Override]
    public function fields(): array
    {
        return [
            'beneficiary' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::input_type.mutation_royalty.field.beneficiary'),
                'rules' => ['filled', new ValidSubstrateAccount()],
            ],
            'percentage' => [
                'type' => GraphQL::type('Float!'),
                'description' => __('enjin-platform::input_type.mutation_royalty.field.percentage'),
                'rules' => [new ValidRoyaltyPercentage()],
            ],
        ];
    }
}
