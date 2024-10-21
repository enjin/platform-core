<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Mutations;

use Closure;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\GraphQL\Middleware\ResolvePage;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\InPrimarySchema;
use Enjin\Platform\GraphQL\Types\Pagination\ConnectionInput;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Enjin\Platform\Services\Database\TransactionService;
use Enjin\Platform\Support\SS58Address;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Builder;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class MarkAndListPendingTransactionsMutation extends Mutation implements PlatformGraphQlMutation
{
    use InPrimarySchema;

    protected $middleware = [
        ResolvePage::class,
    ];

    /**
     * Get the mutation's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'MarkAndListPendingTransactions',
            'description' => __('enjin-platform::mutation.mark_and_list_pending_transactions.description'),
        ];
    }

    /**
     * Get the mutation's return type.
     */
    public function type(): Type
    {
        return GraphQL::paginate('Transaction', 'TransactionConnection');
    }

    /**
     * Get the mutation's arguments definition.
     */
    public function args(): array
    {
        return ConnectionInput::args([
            'accounts' => [
                'type' => GraphQL::type('[String]'),
                'description' => __('enjin-platform::mutation.mark_and_list_pending_transactions.args.accounts'),
            ],
            'network' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::mutation.mark_and_list_pending_transactions.args.network'),
            ],
            'markAsProcessing' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::mutation.mark_and_list_pending_transactions.args.mark_as_processing'),
                'defaultValue' => true,
            ],
        ]);
    }

    /**
     * Resolve the mutation's request.
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields, TransactionService $transactionService): mixed
    {
        $transactions = Transaction::loadSelectFields($resolveInfo, $this->name)
            ->where('state', '=', TransactionState::PENDING->name)
            ->when(
                $args['network'] ?? false,
                fn (Builder $query) => $query->where(
                    'network',
                    $args['network'] === 'relay' ? currentRelay()->name : currentMatrix()->name
                ),
            )
            ->when(
                $args['accounts'] ?? false,
                fn (Builder $query) => $query->whereIn(
                    'wallet_public_key',
                    array_map(fn ($wallet) => SS58Address::getPublicKey($wallet), $args['accounts'])
                ),
                fn (Builder $query) =>  $query->where('managed', true)
            )->cursorPaginateWithTotal('id', $args['first'], false);

        if ($args['markAsProcessing'] === true || $args['markAsProcessing'] === null) {
            $transactions['items']->getCollection()->each(
                fn ($transaction) => $transactionService->update(
                    clone $transaction,
                    ['state' => TransactionState::PROCESSING->name]
                )
            );
        }

        return $transactions;
    }

    /**
     * Get the validation rules.
     */
    protected function rules(array $args = []): array
    {
        return [
            'accounts.*' => [new ValidSubstrateAccount()],
        ];
    }
}
