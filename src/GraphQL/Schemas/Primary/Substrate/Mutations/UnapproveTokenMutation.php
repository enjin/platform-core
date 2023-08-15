<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations;

use Closure;
use Enjin\Platform\GraphQL\Base\Mutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasSkippableRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTokenIdFieldRules;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasIdempotencyField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSimulateField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasTokenIdFields;
use Enjin\Platform\Interfaces\PlatformBlockchainTransaction;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\ApprovalExistsInToken;
use Enjin\Platform\Rules\TokenEncodeExists;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Enjin\Platform\Services\Database\TransactionService;
use Enjin\Platform\Services\Database\WalletService;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Str;
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

        $encodedData = $serializationService->encode($this->getMethodName(), [
            'collectionId' => $args['collectionId'],
            'tokenId' => $this->encodeTokenId($args),
            'operator' => $operatorWallet->public_key,
        ]);

        return Transaction::lazyLoadSelectFields(
            $transactionService->store([
                'method' => $this->getMutationName(),
                'encoded_data' => $encodedData,
                'idempotency_key' => $args['idempotencyKey'] ?? Str::uuid()->toString(),
                'simulate' => $args['simulate'] ?? false,
            ]),
            $resolveInfo
        );
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        return [
            'collectionId' => ['exists:collections,collection_chain_id'],
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
