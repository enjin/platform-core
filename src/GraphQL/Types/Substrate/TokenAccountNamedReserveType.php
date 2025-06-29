<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\Enums\Substrate\PalletIdentifier;
use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Illuminate\Support\Arr;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;

class TokenAccountNamedReserveType extends Type implements PlatformGraphQlType
{
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'TokenAccountNamedReserve',
            'description' => __('enjin-platform::type.token_account_named_reserve.description'),
        ];
    }

    /**
     * Get the type's fields definition.
     */
    #[Override]
    public function fields(): array
    {
        return [
            // Properties
            'pallet' => [
                'type' => GraphQL::type('PalletIdentifier!'),
                'description' => __('enjin-platform::type.token_account_named_reserve.args.pallet'),
                'resolve' => fn($p) => PalletIdentifier::tryFrom(Arr::get($p, 'pallet')),
            ],
            'amount' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::type.token_account_named_reserve.args.amount'),
                'resolve' => fn($p) => Arr::get($p, 'amount'),
            ],
        ];
    }
}
