<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations;

use Closure;
use Enjin\Platform\GraphQL\Base\Mutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
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
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\ValidRoyaltyPercentage;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Database\TransactionService;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;

class MutateTokenMutation extends Mutation implements PlatformBlockchainTransaction, PlatformGraphQlMutation
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

    /**
     * Get the mutation's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'MutateToken',
            'description' => __('enjin-platform::mutation.mutate_token.description'),
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
                'description' => __('enjin-platform::mutation.mutate_collection.args.collectionId'),
            ],
            ...$this->getTokenFields(__('enjin-platform::mutation.mutate_collection.args.tokenId')),
            'mutation' => [
                'type' => GraphQL::type('TokenMutationInput!'),
                'description' => __('enjin-platform::mutation.mutate_collection.args.mutation'),
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
        Substrate $blockchainService
    ): mixed {
        $encodedData = $serializationService->encode($this->getMutationName(), static::getEncodableParams(
            collectionId: $args['collectionId'],
            tokenId: $this->encodeTokenId($args),
            behavior: $blockchainService->getMutateTokenBehavior(Arr::get($args, 'mutation')),
            listingForbidden: Arr::get($args, 'mutation.listingForbidden')
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

    /**
     * Get the validation error messages.
     */
    public function validationErrorMessages(array $args = []): array
    {
        return [
            'mutation.behavior.isCurrency.accepted' => __('enjin-platform::validation.mutation.behavior.isCurrency.accepted'),
        ];
    }

    public static function getEncodableParams(...$params): array
    {
        $behavior = Arr::get($params, 'behavior', null);

        return [
            'collectionId' => gmp_init(Arr::get($params, 'collectionId', 0)),
            'tokenId' => gmp_init(Arr::get($params, 'tokenId', 0)),
            'mutation' => [
                'behavior' => is_array($behavior) ? ['NoMutation' => null] : ['SomeMutation' => $behavior?->toEncodable()],
                'listingForbidden' => Arr::get($params, 'listingForbidden', null),
                'metadata' => null,
            ],
        ];
    }

    /**
     * Get the common rules.
     */
    protected function rulesCommon(array $args): array
    {
        $isBehaviorEmpty = [] === Arr::get($args, 'mutation.behavior');

        return [
            'mutation.behavior' => $isBehaviorEmpty ? [] : ['required_without:mutation.listingForbidden'],
            'mutation.behavior.hasRoyalty.beneficiary' => ['nullable', 'bail', 'required_with:mutation.behavior.hasRoyalty.percentage', new ValidSubstrateAccount()],
            'mutation.behavior.hasRoyalty.percentage' => ['required_with:mutation.behavior.hasRoyalty.beneficiary', new ValidRoyaltyPercentage()],
            'mutation.behavior.isCurrency' => ['sometimes', 'accepted'],
            'mutation.listingForbidden' => $isBehaviorEmpty ? [] : ['required_without:mutation.behavior'],
        ];
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        return [
            'collectionId' => ['exists:collections,collection_chain_id'],
            ...$this->getTokenFieldRulesExist(),
        ];
    }

    /**
     * Get the mutation's validation rules withoud DB rules.
     */
    protected function rulesWithoutValidation(array $args): array
    {
        return $this->getTokenFieldRules();
    }
}
