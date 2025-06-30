<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations;

use Closure;
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
use Enjin\Platform\Models\Substrate\OperatorTransferParams;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MaxTokenBalance;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Support\Address;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Support\SS58Address;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;

class OperatorTransferTokenMutation extends Mutation implements PlatformBlockchainTransaction, PlatformGraphQlMutation
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
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'OperatorTransferToken',
            'description' => __('enjin-platform::mutation.operator_transfer_token.description'),
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
    #[Override]
    public function args(): array
    {
        return [
            'collectionId' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::mutation.common.args.collectionId'),
            ],
            'recipient' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::mutation.batch_set_attribute.args.recipient'),
            ],
            'params' => [
                'type' => GraphQL::type('OperatorTransferParams!'),
                'description' => __('enjin-platform::mutation.operator_transfer_token.args.params'),

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
    ): mixed {
        $encodedData = $serializationService->encode(
            $this->getMethodName() . (currentSpec() >= 1020 ? '' : 'V1013'),
            static::getEncodableParams(
                recipientAccount: $args['recipient'],
                collectionId: $args['collectionId'],
                operatorTransferParams: $blockchainService->getOperatorTransferParams($args['params'])
            )
        );

        return  $this->storeTransaction($args, $encodedData);
    }

    /**
     * Get the serialization service method name.
     */
    #[Override]
    public function getMethodName(): string
    {
        return 'Transfer';
    }

    public static function getEncodableParams(...$params): array
    {
        return [
            'recipient' => [
                'Id' => SS58Address::getPublicKey(Arr::get($params, 'recipientAccount', Address::daemonPublicKey())),
            ],
            'collectionId' => gmp_init(Arr::get($params, 'collectionId', 0)),
            'params' => Arr::get($params, 'operatorTransferParams', new OperatorTransferParams(0, Address::daemonPublicKey(), 0, false))->toEncodable(),
        ];
    }

    /**
     * Get the common rules.
     */
    protected function rulesCommon(array $args): array
    {
        return [
            'recipient' => ['filled', new ValidSubstrateAccount()],
            'params.source' => ['filled', new ValidSubstrateAccount()],
        ];
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        // TODO: We need to have a rule that checks if the signed has approval on the source collection / token and if enough approval balance
        return [
            'collectionId' => ['exists:collection,id'],
            'params.amount' => [new MinBigInt(0), new MaxBigInt(Hex::MAX_UINT128), new MaxTokenBalance()],
            ...$this->getTokenFieldRulesExist('params'),
        ];
    }

    /**
     * Get the mutation's validation rules withoud DB rules.
     */
    protected function rulesWithoutValidation(array $args): array
    {
        return [
            'collectionId' => [new MinBigInt(2000), new MaxBigInt(Hex::MAX_UINT128)],
            'params.amount' => [new MinBigInt(0), new MaxBigInt(Hex::MAX_UINT128)],
            ...$this->getTokenFieldRules('params')];
    }
}
