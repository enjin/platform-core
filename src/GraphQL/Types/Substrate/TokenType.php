<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Types\Pagination\ConnectionInput;
use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Traits\HasSelectFields;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Arr;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;

class TokenType extends Type implements PlatformGraphQlType
{
    use InSubstrateSchema;
    use HasSelectFields;

    /**
     * Get the type's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'Token',
            'description' => __('enjin-platform::type.token.description'),
            'model' => Token::class,
        ];
    }

    /**
     * Get the type's fields definition.
     */
    public function fields(): array
    {
        return [
            // Properties
            'tokenId' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::type.token.field.tokenId'),
                'alias' => 'token_chain_id',
            ],
            'supply' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::type.token.field.supply'),
            ],
            'cap' => [
                'type' => GraphQL::type('TokenMintCapType'),
                'description' => __('enjin-platform::type.token.field.cap'),
            ],
            'capSupply' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform::type.token.field.cap'),
                'alias' => 'cap_supply',
            ],
            'isFrozen' => [
                'type' => GraphQL::type('Boolean!'),
                'description' => __('enjin-platform::type.token.field.isFrozen'),
                'alias' => 'is_frozen',
            ],
            'isCurrency' => [
                'type' => GraphQL::type('Boolean!'),
                'description' => __('enjin-platform::type.token.field.isCurrency'),
                'alias' => 'is_currency',
            ],
            'royalty' => [
                'type' => GraphQL::type('Royalty'),
                'description' => __('enjin-platform::type.token.field.royalty'),
                'resolve' => function ($token) {
                    if (null === $token->royaltyBeneficiary) {
                        return;
                    }

                    return [
                        'beneficiary' => $token->royaltyBeneficiary,
                        'percentage' => $token->royalty_percentage,
                    ];
                },
                'is_relation' => false,
                'selectable' => false,
                'always' => ['royalty_wallet_id', 'royalty_percentage'],
            ],
            'minimumBalance' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::type.token.field.minimumBalance'),
                'alias' => 'minimum_balance',
            ],
            'unitPrice' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::type.token.field.unitPrice'),
                'alias' => 'unit_price',
            ],
            'attributeCount' => [
                'type' => GraphQL::type('Int!'),
                'description' => __('enjin-platform::type.token.field.attributeCount'),
                'alias' => 'attribute_count',
            ],

            // Related
            'collection' => [
                'type' => GraphQL::type('Collection!'),
                'description' => __('enjin-platform::type.token.field.collection'),
                'is_relation' => true,
            ],
            'attributes' => [
                'type' => GraphQL::type('[Attribute]'),
                'description' => __('enjin-platform::type.token.field.attributes'),
                'is_relation' => true,
            ],
            'accounts' => [
                'type' => GraphQL::paginate('TokenAccount', 'TokenAccountConnection'),
                'description' => __('enjin-platform::type.token.field.accounts'),
                'args' => ConnectionInput::args(),
                'resolve' => function ($token, $args) {
                    return [
                        'items' => new CursorPaginator(
                            $token?->accounts,
                            $args['first'],
                            Arr::get($args, 'after') ? Cursor::fromEncoded($args['after']) : null,
                            ['parameters'=>['id']]
                        ),
                        'total' => (int) $token?->accounts_count,
                    ];
                },
                'is_relation' => true,
            ],

            // Computed
            'mintDeposit' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::type.token.field.mintDeposit'),
                'alias' => 'mint_deposit',
                'selectable' => false,
            ],
            'metadata' => [
                'type' => GraphQL::type('Object'),
                'selectable' => false,
            ],
            'nonFungible' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::type.token.field.nonFungible'),
                'alias' => 'non_fungible',
                'selectable' => false,
            ],
        ];
    }
}
