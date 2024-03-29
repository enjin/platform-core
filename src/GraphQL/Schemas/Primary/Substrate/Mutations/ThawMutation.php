<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations;

use Closure;
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

class ThawMutation extends Mutation implements PlatformBlockchainTransaction, PlatformGraphQlMutation
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
    public function attributes(): array
    {
        return [
            'name' => 'Thaw',
            'description' => __('enjin-platform::mutation.thaw.description'),
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
            'freezeType' => [
                'type' => GraphQL::type('FreezeType!'),
                'description' => __('enjin-platform::mutation.thaw.args.freezeType'),
            ],
            'collectionId' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::mutation.thaw.args.collectionId'),
            ],
            ...$this->getTokenFields(__('enjin-platform::mutation.thaw.args.tokenId'), true),
            'collectionAccount' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::mutation.thaw.description'),
            ],
            'tokenAccount' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::mutation.thaw.args.tokenAccount'),
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
            thawParams: $params,
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
            'freezeType' => Arr::get($params, 'thawParams', new FreezeTypeParams(FreezeType::TOKEN))->toEncodable(),
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
                    ? $this->getTokenFieldRulesExist(null, [], false)
                    : ['tokenId' => ['prohibited']]
            ),
            'collectionAccount' => $freezeType === FreezeType::COLLECTION_ACCOUNT ? ['bail', 'required', new ValidSubstrateAccount(), new AccountExistsInCollection()] : ['prohibited'],
            'tokenAccount' => $freezeType === FreezeType::TOKEN_ACCOUNT ? ['bail', 'required', new ValidSubstrateAccount(), new AccountExistsInToken()] : ['prohibited'],
        ];
    }

    /**
     * Get the mutation's validation rules without DB rules.
     */
    protected function rulesWithoutValidation(array $args): array
    {
        $freezeType = FreezeType::getEnumCase($args['freezeType']);

        return [
            ...(
                in_array($freezeType, [FreezeType::TOKEN, FreezeType::TOKEN_ACCOUNT], true)
                    ? $this->getTokenFieldRules()
                    : ['tokenId' => ['prohibited'], 'encodeTokenId.data' => ['prohibited']]
            ),
            'collectionAccount' => $freezeType === FreezeType::COLLECTION_ACCOUNT ? ['bail', 'required', new ValidSubstrateAccount()] : ['prohibited'],
            'tokenAccount' => $freezeType === FreezeType::TOKEN_ACCOUNT ? ['bail', 'required', new ValidSubstrateAccount()] : ['prohibited'],
        ];
    }
}
