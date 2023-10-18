<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations;

use Closure;
use Enjin\Platform\Enums\Substrate\FreezeType;
use Enjin\Platform\GraphQL\Base\Mutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
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
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Database\TransactionService;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;

class ThawMutation extends Mutation implements PlatformBlockchainTransaction, PlatformGraphQlMutation
{
    use InPrimarySubstrateSchema;
    use HasIdempotencyField;
    use HasTokenIdFields;
    use HasTokenIdFieldRules;
    use HasSkippableRules;
    use HasSimulateField;
    use HasTransactionDeposit;
    use HasSigningAccountField;

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
            $transactionService->store(
                [
                    'method' => $this->getMutationName(),
                    'encoded_data' => $encodedData,
                    'idempotency_key' => $args['idempotencyKey'] ?? Str::uuid()->toString(),
                    'deposit' => $this->getDeposit($args),
                    'simulate' => $args['simulate'],
                ],
                signingWallet: $this->getSigningAccount($args),
            ),
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
            'collectionId' => ['exists:collections,collection_chain_id'],
            ...(
                in_array($freezeType, [FreezeType::TOKEN, FreezeType::TOKEN_ACCOUNT], true)
                    ? $this->getTokenFieldRulesExist(null, [], false)
                    : ['tokenId' => ['prohibited']]
            ),
            'collectionAccount' => FreezeType::COLLECTION_ACCOUNT === $freezeType ? ['bail', 'required', new ValidSubstrateAccount(), new AccountExistsInCollection()] : ['prohibited'],
            'tokenAccount' => FreezeType::TOKEN_ACCOUNT === $freezeType ? ['bail', 'required', new ValidSubstrateAccount(), new AccountExistsInToken()] : ['prohibited'],
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
            'collectionAccount' => FreezeType::COLLECTION_ACCOUNT === $freezeType ? ['bail', 'required', new ValidSubstrateAccount()] : ['prohibited'],
            'tokenAccount' => FreezeType::TOKEN_ACCOUNT === $freezeType ? ['bail', 'required', new ValidSubstrateAccount()] : ['prohibited'],
        ];
    }
}
