<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations;

use Closure;
use Enjin\Platform\Enums\Substrate\FreezeStateType;
use Enjin\Platform\Enums\Substrate\FreezeType;
use Enjin\Platform\GraphQL\Base\Mutation;
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
use Enjin\Platform\Models\Substrate\FreezeTypeParams;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\AccountExistsInCollection;
use Enjin\Platform\Rules\AccountExistsInToken;
use Enjin\Platform\Rules\IsCollectionOwner;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Database\TransactionService;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Rebing\GraphQL\Support\Facades\GraphQL;

class FreezeMutation extends Mutation implements PlatformBlockchainTransaction, PlatformGraphQlMutation
{
    use HasIdempotencyField;
    use HasSigningAccountField;
    use HasSimulateField;
    use HasSkippableRules;
    use HasTokenIdFieldRules;
    use HasTokenIdFields;
    use HasTransactionDeposit;
    use InPrimarySubstrateSchema;
    use StoresTransactions;

    /**
     * Get the mutation's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'Freeze',
            'description' => __('enjin-platform::mutation.freeze.description'),
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
    #[\Override]
    public function args(): array
    {
        return [
            'freezeType' => [
                'type' => GraphQL::type('FreezeType!'),
                'description' => __('enjin-platform::mutation.freeze.args.freezeType'),
            ],
            'collectionId' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::mutation.freeze.args.collectionId'),
            ],
            ...$this->getTokenFields(__('enjin-platform::mutation.freeze.args.tokenId'), true),
            'freezeState' => [
                'type' => GraphQL::type('FreezeStateType'),
                'description' => __('enjin-platform::mutation.freeze.args.freezeState'),
            ],
            'collectionAccount' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::mutation.freeze.args.collectionAccount'),
            ],
            'tokenAccount' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::mutation.freeze.args.tokenAccount'),
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
        TransactionService $transactionService
    ): mixed {
        $params = $blockchainService->getFreezeOrThawParams($args);
        $encodedData = $serializationService->encode($this->getMutationName(), static::getEncodableParams(
            collectionId: $args['collectionId'],
            freezeParams: $params
        ));

        return Transaction::lazyLoadSelectFields(
            $this->storeTransaction($args, $encodedData),
            $resolveInfo
        );
    }

    public static function getEncodableParams(...$params): array
    {
        $freezeType = Arr::get($params, 'freezeParams', new FreezeTypeParams(FreezeType::TOKEN));

        if (is_null($freezeType->freezeState) && $freezeType->type === FreezeType::TOKEN) {
            $freezeType->freezeState = FreezeStateType::TEMPORARY;
        }

        return [
            'collectionId' => gmp_init(Arr::get($params, 'collectionId', 0)),
            'freezeType' => $freezeType->toEncodable(),
        ];
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        $freezeType = FreezeType::getEnumCase($args['freezeType']);

        return [
            'collectionId' => [new IsCollectionOwner()],
            ...(
                in_array($freezeType, [FreezeType::TOKEN, FreezeType::TOKEN_ACCOUNT], true)
                    ? $this->getTokenFieldRulesExist(null, [])
                    : ['tokenId' => ['prohibited']]
            ),
            'freezeState' => $freezeType !== FreezeType::TOKEN ? ['prohibited'] : ['nullable'],
            'collectionAccount' => $freezeType === FreezeType::COLLECTION_ACCOUNT ? ['bail', 'required', new ValidSubstrateAccount(), new AccountExistsInCollection()] : ['prohibited'],
            'tokenAccount' => $freezeType === FreezeType::TOKEN_ACCOUNT ? ['bail', 'required', new ValidSubstrateAccount(), new AccountExistsInToken()] : ['prohibited'],
        ];
    }

    /**
     * Get the mutation's validation rules withoud DB rules.
     */
    protected function rulesWithoutValidation(array $args): array
    {
        $freezeType = FreezeType::getEnumCase($args['freezeType']);

        return [
            ...(
                in_array($freezeType, [FreezeType::TOKEN, FreezeType::TOKEN_ACCOUNT], true)
                    ? $this->getTokenFieldRules()
                    : ['tokenId' => ['prohibited'], 'encodeTokenId' => ['prohibited']]
            ),
            'freezeState' => $freezeType !== FreezeType::TOKEN ? ['prohibited'] : ['nullable'],
            'collectionAccount' => $freezeType === FreezeType::COLLECTION_ACCOUNT ? ['bail', 'required', new ValidSubstrateAccount()] : ['prohibited'],
            'tokenAccount' => $freezeType === FreezeType::TOKEN_ACCOUNT ? ['bail', 'required', new ValidSubstrateAccount()] : ['prohibited'],
        ];
    }
}
