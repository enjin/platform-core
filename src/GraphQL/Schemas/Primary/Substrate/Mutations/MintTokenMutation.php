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
use Enjin\Platform\Interfaces\PlatformBlockchainTransaction;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Models\Substrate\MintParams;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\IsCollectionOwner;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Database\TransactionService;
use Enjin\Platform\Services\Database\WalletService;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use Enjin\Platform\Support\Account;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Rebing\GraphQL\Support\Facades\GraphQL;

class MintTokenMutation extends Mutation implements PlatformBlockchainTransaction, PlatformGraphQlMutation
{
    use InPrimarySubstrateSchema;
    use HasIdempotencyField;
    use HasTokenIdFieldRules;
    use HasSkippableRules;
    use HasSimulateField;
    use HasTransactionDeposit;
    use HasEncodableTokenId;
    use HasTransactionDeposit;
    use HasSigningAccountField;
    use StoresTransactions;

    /**
     * Get the mutation's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'MintToken',
            'description' => __('enjin-platform::mutation.mint_token.description'),
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
            'recipient' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::mutation.mint_token.args.recipient'),
                'rules' => ['filled', new ValidSubstrateAccount()],
            ],
            'collectionId' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::mutation.mint_token.args.collectionId'),
                'rules' => [new IsCollectionOwner()],
            ],
            'params' => [
                'type' => GraphQL::type('MintTokenParams!'),
                'description' => __('enjin-platform::input_type.mint_token_params.description'),
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
        Substrate $blockchainService,
        SerializationServiceInterface $serializationService,
        TransactionService $transactionService,
        WalletService $walletService
    ): mixed {
        $recipientWallet = $walletService->firstOrStore(['account' => $args['recipient']]);
        $encodedData = $serializationService->encode($this->getMethodName(), static::getEncodableParams(
            recipientAccount: $recipientWallet->public_key,
            collectionId: $args['collectionId'],
            mintTokenParams: $blockchainService->getMintTokenParams($args['params'])
        ));

        return Transaction::lazyLoadSelectFields(
            $this->storeTransaction($args, $encodedData),
            $resolveInfo
        );
    }

    /**
     * Get the serialization service method name.
     */
    public function getMethodName(): string
    {
        return 'Mint';
    }

    public static function getEncodableParams(...$params): array
    {
        return [
            'recipient' => [
                'Id' => HexConverter::unPrefix(Arr::get($params, 'recipientAccount', Account::daemonPublicKey())),
            ],
            'collectionId' => gmp_init(Arr::get($params, 'collectionId', 0)),
            'params' => Arr::get($params, 'mintTokenParams', new MintParams(0, 0))->toEncodable(),
        ];
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        return $this->getTokenFieldRulesExist('params');
    }

    /**
     * Get the mutation's validation rules withoud DB rules.
     */
    protected function rulesWithoutValidation(array $args): array
    {
        return $this->getTokenFieldRules('params');
    }
}
