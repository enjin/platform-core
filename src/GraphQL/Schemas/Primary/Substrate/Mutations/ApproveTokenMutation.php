<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations;

use Closure;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\GraphQL\Base\Mutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\StoresTransactions;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasSkippableRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTokenIdFieldRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTransactionDeposit;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasIdempotencyField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSigningAccountField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSimulateField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasTokenIdFields;
use Enjin\Platform\Interfaces\PlatformBlockchainTransaction;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\DaemonProhibited;
use Enjin\Platform\Rules\FutureBlock;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Rules\TokenEncodeExists;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Enjin\Platform\Services\Database\TransactionService;
use Enjin\Platform\Services\Database\WalletService;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\Hex;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Rebing\GraphQL\Support\Facades\GraphQL;

class ApproveTokenMutation extends Mutation implements PlatformBlockchainTransaction, PlatformGraphQlMutation
{
    use InPrimarySubstrateSchema;
    use HasIdempotencyField;
    use HasTokenIdFields;
    use HasTokenIdFieldRules;
    use HasEncodableTokenId;
    use HasSkippableRules;
    use HasSimulateField;
    use HasTransactionDeposit;
    use HasSigningAccountField;
    use StoresTransactions;

    /**
     * Get the mutation's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'ApproveToken',
            'description' => __('enjin-platform::mutation.approve_token.description'),
        ];
    }

    /**
     * Get the mutation's return type.
     */
    public function type(): Type
    {
        return GraphQL::type('Transaction!');
    }

    /**
     * Get the mutation's arguments definition.
     */
    public function args(): array
    {
        return [
            ...$this->getTokenFields(__('enjin-platform::mutation.approve_token.args.tokenId')),
            'collectionId' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::mutation.approve_token.args.collectionId'),
                'rules' => ['exists:collections,collection_chain_id'],
            ],
            'operator' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::mutation.approve_token.args.operator'),
                'rules' => ['filled', new ValidSubstrateAccount(), new DaemonProhibited()],
            ],
            'amount' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::mutation.approve_token.args.amount'),
                'rules' => [new MinBigInt(1), new MaxBigInt(Hex::MAX_UINT128)],
            ],
            'currentAmount' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::mutation.approve_token.args.currentAmount'),
                'rules' => [new MinBigInt(), new MaxBigInt(Hex::MAX_UINT128)],
            ],
            'expiration' => [
                'type' => GraphQL::type('Int'),
                'description' => __('enjin-platform::mutation.approve_token.args.expiration'),
                'rules' => ['nullable', 'integer', new FutureBlock()],
                'defaultValue' => null,
            ],
            ...$this->getSigningAccountField(),
            ...$this->getIdempotencyField(),
            ...$this->getSkipValidationField(),
            ...$this->getSimulateField(),
        ];
    }

    /**
     * Resolve the mutation's request.
     */
    public function resolve(
        $root,
        array $args,
        $context,
        ResolveInfo $resolveInfo,
        Closure $getSelectFields,
        SerializationServiceInterface $serializationService,
        TransactionService $transactionService,
        WalletService $walletService
    ): mixed {
        $operatorWallet = $walletService->firstOrStore(['account' => $args['operator']]);
        $encodedData = $serializationService->encode($this->getMutationName(), static::getEncodableParams(
            collectionId: $args['collectionId'],
            tokenId: $this->encodeTokenId($args),
            operator: $operatorWallet->public_key,
            amount: $args['amount'],
            currentAmount: $args['currentAmount'],
            expiration: $args['expiration']
        ));

        return Transaction::lazyLoadSelectFields(
            $this->storeTransaction($args, $encodedData),
            $resolveInfo
        );
    }

    public static function getEncodableParams(...$params): array
    {
        return [
            'collectionId' => gmp_init(Arr::get($params, 'collectionId', 0)),
            'tokenId' => gmp_init(Arr::get($params, 'tokenId', 0)),
            'operator' => HexConverter::unPrefix(Arr::get($params, 'operator', Account::daemonPublicKey())),
            'amount' => gmp_init(Arr::get($params, 'amount', 0)),
            'currentAmount' => gmp_init(Arr::get($params, 'currentAmount', 0)),
            'expiration' => Arr::get($params, 'expiration', null),
        ];
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        return $this->getTokenFieldRules(
            null,
            [new TokenEncodeExists()]
        );
    }

    /**
     * Get the mutation's validation rules without DB rules.
     */
    protected function rulesWithoutValidation(array $args): array
    {
        return $this->getTokenFieldRules();
    }
}
