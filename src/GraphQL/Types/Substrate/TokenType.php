<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\Enums\Substrate\TokenMintCapType;
use Enjin\Platform\GraphQL\Types\Pagination\ConnectionInput;
use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Traits\HasSelectFields;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Arr;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type;

class TokenType extends Type implements PlatformGraphQlType
{
    use HasSelectFields;
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    #[\Override]
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
    #[\Override]
    public function fields(): array
    {
        return [
            // Properties
            'tokenId' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::type.token.field.tokenId'),
                'alias' => 'token_id',
            ],
            'supply' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::type.token.field.supply'),
            ],
            'cap' => [
                'type' => GraphQL::type('TokenMintCapType'),
                'description' => __('enjin-platform::type.token.field.cap'),
                'resolve' => fn ($t) => match(Arr::get($t->cap, 'type')) {
                    'Supply' => TokenMintCapType::SUPPLY,
                    'SingleMint' => TokenMintCapType::COLLAPSING_SUPPLY,
                    default => null,
                },
            ],
            'capSupply' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform::type.token.field.cap'),
                'resolve' => fn ($t) => Arr::get($t->cap, 'supply'),
            ],
            'isFrozen' => [
                'type' => GraphQL::type('Boolean!'),
                'description' => __('enjin-platform::type.token.field.isFrozen'),
                'alias' => 'is_frozen',
            ],
            'isCurrency' => [
                'type' => GraphQL::type('Boolean!'),
                'description' => __('enjin-platform::type.token.field.isCurrency'),
                'alias' => 'behavior',
                'resolve' => fn ($t) => Arr::get($t->behavior, 'type') === 'IsCurrency',
            ],
            'royalty' => [
                'type' => GraphQL::type('Royalty'),
                'description' => __('enjin-platform::type.token.field.royalty'),
                'alias' => 'behavior',
                'is_relation' => false,
                'resolve' => function ($t): ?array {
                    if (empty($beneficiary = Arr::get($t->behavior, 'beneficiaries.0'))) {
                        return null;
                    }

                    $wallet = Wallet::firstWhere('id', Arr::get($beneficiary, 'accountId'));
                    if (!$wallet) {
                        return null;
                    }

                    return [
                        'beneficiary' => $wallet,
                        'percentage' => Arr::get($beneficiary, 'percentage'),
                    ];
                },
            ],
            'attributeCount' => [
                'type' => GraphQL::type('Int!'),
                'description' => __('enjin-platform::type.token.field.attributeCount'),
                'alias' => 'attribute_count',
            ],
            //            'requiresDeposit' => [
            //                'type' => GraphQL::type('Boolean!'),
            //                'description' => __('enjin-platform::type.token.field.requiresDeposit'),
            //                'alias' => 'requires_deposit',
            //            ],
            //            'creationDeposit' => [
            //                'type' => GraphQL::type('CreationDeposit!'),
            //                'description' => __('enjin-platform::type.collection_type.field.creationDeposit'),
            //                'resolve' => fn ($token) => [
            //                    'depositor' => $token->creationDepositor,
            //                    'amount' => $token->creation_deposit_amount,
            //                ],
            //                'is_relation' => false,
            //                'selectable' => false,
            //            ],
            //            'ownerDeposit' => [
            //                'type' => GraphQL::type('BigInt!'),
            //                'description' => __('enjin-platform::type.token.field.ownerDeposit'),
            //                'alias' => 'owner_deposit',
            //            ],
            //            'totalTokenAccountDeposit' => [
            //                'type' => GraphQL::type('BigInt!'),
            //                'description' => __('enjin-platform::type.token.field.totalTokenAccountDeposit'),
            //                'alias' => 'total_token_account_deposit',
            //            ],
            'infusion' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::type.token.field.infusion'),
            ],
            'anyoneCanInfuse' => [
                'type' => GraphQL::type('Boolean!'),
                'description' => __('enjin-platform::type.token.field.anyoneCanInfuse'),
                'alias' => 'anyone_can_infuse',
            ],
            //            'tokenMetadata' => [
            //                'type' => GraphQL::type('TokenMetadata!'),
            //                'description' => __('enjin-platform::type.token.field.tokenMetadata'),
            //                'resolve' => fn ($token) => [
            //                    'name' => $token->name,
            //                    'symbol' => $token->symbol,
            //                    'decimalCount' => $token->decimal_count,
            //                ],
            //                'is_relation' => false,
            //                'selectable' => false,
            //            ],
            'metadata' => [
                'type' => GraphQL::type('Object'),
            ],
            'nonFungible' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::type.token.field.nonFungible'),
                'alias' => 'non_fungible',
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
                'is_relation' => true,
                'resolve' => fn ($token, $args) => [
                    'items' => new CursorPaginator(
                        $token?->accounts,
                        $args['first'],
                        Arr::get($args, 'after') ? Cursor::fromEncoded($args['after']) : null,
                        ['parameters' => ['id']]
                    ),
                    'total' => (int) $token?->accounts_count,
                ],
            ],

            // Deprecated
            'minimumBalance' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform::type.token.field.minimumBalance'),
                'deprecationReason' => __('enjin-platform::deprecated.token.field.minimumBalance'),
                'alias' => 'minimum_balance',
            ],
            'unitPrice' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform::type.token.field.unitPrice'),
                'deprecationReason' => __('enjin-platform::deprecated.token.field.unitPrice'),
                'alias' => 'unit_price',
            ],
            'mintDeposit' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform::type.token.field.mintDeposit'),
                'deprecationReason' => __('enjin-platform::deprecated.token.field.mintDeposit'),
                'alias' => 'mint_deposit',
            ],
        ];
    }
}
