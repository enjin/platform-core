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
use Enjin\Platform\Rules\ApprovalExistsInToken;
use Enjin\Platform\Rules\IsCollectionOwner;
use Enjin\Platform\Rules\TokenEncodeExists;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Enjin\Platform\Services\Database\TransactionService;
use Enjin\Platform\Services\Database\WalletService;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use Enjin\Platform\Support\Account;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Rebing\GraphQL\Support\Facades\GraphQL;

class UnapproveTokenMutation extends Mutation implements PlatformBlockchainTransaction, PlatformGraphQlMutation
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
            'name' => 'UnapproveToken',
            'description' => __('enjin-platform::mutation.unapprove_token.description'),
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
            'collectionId' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::mutation.unapprove_token.args.collectionId'),
            ],
            ...$this->getTokenFields(__('enjin-platform::mutation.unapprove_token.args.tokenId')),
            'operator' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::mutation.unapprove_token.args.operator'),
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
        ];
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        return [
            'collectionId' => [new IsCollectionOwner()],
            'operator' => ['bail', 'filled', new ValidSubstrateAccount(), new ApprovalExistsInToken()],
            ...$this->getTokenFieldRules(
                null,
                [new TokenEncodeExists()]
            ),
        ];
    }

    /**
     * Get the mutation's validation rules without DB rules.
     */
    protected function rulesWithoutValidation(array $args): array
    {
        return [
            'operator' => ['bail', 'filled', new ValidSubstrateAccount()],
            ...$this->getTokenFieldRules(),
        ];
    }
}
