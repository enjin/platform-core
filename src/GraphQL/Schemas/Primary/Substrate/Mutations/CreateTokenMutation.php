<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations;

use Closure;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Enums\Substrate\TokenMintCapType;
use Enjin\Platform\GraphQL\Base\Mutation;
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
use Enjin\Platform\Models\Substrate\CreateTokenParams;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\CheckTokenCount;
use Enjin\Platform\Rules\IsCollectionOwner;
use Enjin\Platform\Rules\MinBigInt;
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

class CreateTokenMutation extends Mutation implements PlatformBlockchainTransaction, PlatformGraphQlMutation
{
    use HasIdempotencyField;
    use HasSigningAccountField;
    use HasSimulateField;
    use HasSkippableRules;
    use HasTokenIdFieldRules;
    use HasTransactionDeposit;
    use InPrimarySubstrateSchema;
    use StoresTransactions;

    /**
     * Get the mutation's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'CreateToken',
            'description' => __('enjin-platform::mutation.create_token.description'),
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
                'description' => __('enjin-platform::mutation.create_token.args.recipient'),
            ],
            'collectionId' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::mutation.burn.args.collectionId'),
            ],
            'params' => [
                'type' => GraphQL::type('CreateTokenParams!'),
                'description' => __('enjin-platform::input_type.create_token_params.description'),
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
            createTokenParams: $blockchainService->getCreateTokenParams($args['params'])
        ));
        $encodedData .= isRunningLatest() ? '00000000' : '';

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
        return isRunningLatest() ? 'MintV1010' : 'Mint';
    }

    public static function getEncodableParams(...$params): array
    {
        return [
            'recipient' => [
                'Id' => HexConverter::unPrefix(Arr::get($params, 'recipientAccount', Account::daemonPublicKey())),
            ],
            'collectionId' => gmp_init(Arr::get($params, 'collectionId', 0)),
            'params' => Arr::get($params, 'createTokenParams', new CreateTokenParams(0, 0, TokenMintCapType::INFINITE))->toEncodable(),
        ];
    }

    /**
     * Get common rules.
     */
    protected function rulesCommon(array $args): array
    {
        return [
            'recipient' => ['filled', new ValidSubstrateAccount()],
            'params.cap.amount' => [
                'required_if:params.cap.type,SUPPLY',
                'prohibited_if:params.cap.type,SINGLE_MINT',
                'nullable',
                new MinBigInt($args['params']['initialSupply']),
            ],
        ];
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        return [
            'collectionId' => [new IsCollectionOwner(), new CheckTokenCount()],
            ...$this->getTokenFieldRulesDoesntExist('params'),
        ];
    }

    /**
     * Get the mutation's validation rules without DB rules.
     */
    protected function rulesWithoutValidation(array $args): array
    {
        return $this->getTokenFieldRules('params');
    }
}
