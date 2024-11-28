<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate;

use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasTokenIdFields;
use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Support\Hex;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\InputType;

class MultiTokenIdInputType extends InputType implements PlatformGraphQlType
{
    use HasTokenIdFields;
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'MultiTokenIdInput',
            'description' => __('enjin-platform::input_type.multi_token_id.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    #[\Override]
    public function fields(): array
    {
        return [
            'collectionId' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::input_type.multi_token_id.field.collectionId'),
                'rules' => [new MinBigInt(), new MaxBigInt(Hex::MAX_UINT128)],
            ],
            ...$this->getTokenFields(__('enjin-platform::input_type.multi_token_id.field.tokenId')),
        ];
    }
}
