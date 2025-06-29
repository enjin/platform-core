<?php

namespace Enjin\Platform\GraphQL\Types\Substrate;

use Enjin\Platform\GraphQL\Types\Pagination\ConnectionInput;
use Enjin\Platform\GraphQL\Types\Traits\InSubstrateSchema;
use Enjin\Platform\Interfaces\PlatformGraphQlType;
use Enjin\Platform\Models\Transaction;
use Facades\Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Arr;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class TransactionType extends GraphQLType implements PlatformGraphQlType
{
    use InSubstrateSchema;

    /**
     * Get the type's attributes.
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'Transaction',
            'description' => __('enjin-platform::type.transaction.description'),
            'model' => Transaction::class,
        ];
    }

    /**
     * Get the type's fields definition.
     */
    #[Override]
    public function fields(): array
    {
        return [
            'id' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::query.get_transaction.args.id'),
            ],
            'transactionId' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::type.transaction.field.transactionId'),
                'deprecationReason' => '',
                'alias' => 'id',
            ],
            'transactionHash' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::type.transaction.field.transactionHash'),
                'alias' => 'hash',
            ],
            'method' => [
                'type' => GraphQL::type('TransactionMethod'),
                'description' => __('enjin-platform::type.transaction.field.method'),
            ],
            'state' => [
                'type' => GraphQL::type('TransactionState!'),
                'description' => __('enjin-platform::type.transaction.field.state'),
            ],
            'result' => [
                'type' => GraphQL::type('TransactionResult'),
                'description' => __('enjin-platform::type.transaction.field.result'),
            ],
            'encodedData' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::type.transaction.field.encodedData'),
                'alias' => 'encoded_data',
            ],
            'signingPayload' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::type.transaction.field.signingPayload'),
                'args' => [
                    'nonce' => [
                        'type' => GraphQL::type('Int'),
                        'description' => __('enjin-platform::type.transaction.field.signingPayload.nonce'),
                        'defaultValue' => 0,
                    ],
                    'tip' => [
                        'type' => GraphQL::type('BigInt'),
                        'description' => __('enjin-platform::type.transaction.field.signingPayload.tip'),
                        'defaultValue' => '0',
                    ],
                ],
                'resolve' => fn ($transaction, $args) => Substrate::getSigningPayload($transaction['encoded_data'], $args),
                'selectable' => false,
            ],
            'signingPayloadJson' => [
                'type' => GraphQL::type('Json'),
                'description' => __('enjin-platform::type.transaction.field.signingPayload'),
                'args' => [
                    'nonce' => [
                        'type' => GraphQL::type('Int'),
                        'description' => __('enjin-platform::type.transaction.field.signingPayload.nonce'),
                        'defaultValue' => 0,
                    ],
                    'tip' => [
                        'type' => GraphQL::type('BigInt'),
                        'description' => __('enjin-platform::type.transaction.field.signingPayload.tip'),
                        'defaultValue' => '0',
                    ],
                ],
                'resolve' => fn ($transaction, $args) => Substrate::getSigningPayloadJSON($transaction, $args),
                'selectable' => false,
            ],
            'fee' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform::type.transaction.field.fee'),
                'resolve' => fn ($transaction) => isset($transaction['idempotency_key']) ? Arr::get($transaction, 'fee') : Substrate::getFee($transaction['encoded_data']),
            ],
            'deposit' => [
                'type' => GraphQL::type('BigInt'),
                'description' => __('enjin-platform::type.transaction.field.deposit'),
            ],
            'wallet' => [
                'type' => GraphQL::type('Wallet'),
                'description' => __('enjin-platform::type.transaction.field.wallet'),
                'is_relation' => true,
            ],
            'network' => [
                'type' => GraphQL::type('NetworkType!'),
                'description' => __('enjin-platform::type.transaction.field.network'),
            ],
            'idempotencyKey' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::type.transaction.field.idempotencyKey'),
                'alias' => 'idempotency_key',
            ],
            'signedAtBlock' => [
                'type' => GraphQL::type('Int'),
                'description' => __('enjin-platform::type.transaction.field.signedAtBlock'),
                'alias' => 'signed_at_block',
            ],
            'createdAt' => [
                'type' => GraphQL::type('DateTime!'),
                'description' => __('enjin-platform::type.transaction.field.createdAt'),
                'alias' => 'created_at',
            ],
            'updatedAt' => [
                'type' => GraphQL::type('DateTime!'),
                'description' => __('enjin-platform::type.transaction.field.updatedAt'),
                'alias' => 'updated_at',
            ],

            // Related
//            'events' => [
//                'type' => GraphQL::paginate('Event', 'EventConnection'),
//                'description' => __('enjin-platform::type.transaction.field.events'),
//                'args' => ConnectionInput::args(),
//                'resolve' => fn ($transaction, $args) => [
//                    'items' => new CursorPaginator(
//                        $transaction?->events,
//                        $args['first'],
//                        Arr::get($args, 'after') ? Cursor::fromEncoded($args['after']) : null,
//                        ['parameters' => ['id']]
//                    ),
//                    'total' => (int) $transaction?->events_count,
//                ],
//                'is_relation' => true,
//            ],
        ];
    }
}
