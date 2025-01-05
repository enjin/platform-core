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
use Enjin\Platform\Rules\IsCollectionOwner;
use Enjin\Platform\Rules\ValidRoyaltyPercentage;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Database\TransactionService;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Rebing\GraphQL\Support\Facades\GraphQL;

class MutateTokenMutation extends Mutation implements PlatformBlockchainTransaction, PlatformGraphQlMutation
{
    use HasEncodableTokenId;
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
    #[\Override]
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
                'description' => __('enjin-platform::mutation.mutate_token.args.mutation'),
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
            listingForbidden: Arr::get($args, 'mutation.listingForbidden'),
            anyoneCanInfuse: Arr::get($args, 'mutation.anyoneCanInfuse'),
            name: Arr::get($args, 'mutation.name'),
        ));

        return Transaction::lazyLoadSelectFields(
            $this->storeTransaction($args, $encodedData),
            $resolveInfo
        );
    }

    /**
     * Get the validation error messages.
     */
    #[\Override]
    public function validationErrorMessages(array $args = []): array
    {
        return [
            'mutation.behavior.isCurrency.accepted' => __('enjin-platform::validation.mutation.behavior.isCurrency.accepted'),
        ];
    }

    public static function getEncodableParams(...$params): array
    {
        $behavior = Arr::get($params, 'behavior');

        return [
            'collectionId' => gmp_init(Arr::get($params, 'collectionId', 0)),
            'tokenId' => gmp_init(Arr::get($params, 'tokenId', 0)),
            'mutation' => [
                'behavior' => (is_array($behavior) || !isset($behavior)) ? ['NoMutation' => null] : ['SomeMutation' => $behavior?->toEncodable()],
                'listingForbidden' => Arr::get($params, 'listingForbidden'),
                'anyoneCanInfuse' => Arr::get($params, 'anyoneCanInfuse'),
                'name' => ($name = Arr::get($params, 'name')) ? HexConverter::stringToHexPrefixed($name) : null,
            ],
        ];
    }

    /**
     * Get the common rules.
     */
    protected function rulesCommon(array $args): array
    {
        $isBehaviorEmpty = Arr::get($args, 'mutation.behavior') === [];

        return [
            'mutation.behavior' => $isBehaviorEmpty ? [] : ['required_without_all:mutation.listingForbidden,mutation.anyoneCanInfuse,mutation.name'],
            'mutation.behavior.hasRoyalty.beneficiary' => ['nullable', 'bail', 'required_with:mutation.behavior.hasRoyalty.percentage', new ValidSubstrateAccount()],
            'mutation.behavior.hasRoyalty.percentage' => ['required_with:mutation.behavior.hasRoyalty.beneficiary', new ValidRoyaltyPercentage()],
            'mutation.behavior.isCurrency' => ['sometimes', 'accepted'],
            'mutation.listingForbidden' => $isBehaviorEmpty ? [] : ['required_without_all:mutation.behavior,mutation.anyoneCanInfuse,mutation.name'],
            'mutation.anyoneCanInfuse' => $isBehaviorEmpty ? [] : ['required_without_all:mutation.behavior,mutation.listingForbidden,mutation.name'],
            'mutation.name' => $isBehaviorEmpty ? [] : ['required_without_all:mutation.behavior,mutation.listingForbidden,mutation.anyoneCanInfuse'],
        ];
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        return [
            'collectionId' => [new IsCollectionOwner()],
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
