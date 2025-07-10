<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Mutations;

use Closure;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\GraphQL\Base\Mutation;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\InPrimarySubstrateSchema;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\StoresTransactions;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasSkippableRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTransactionDeposit;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasIdempotencyField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSigningAccountField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSimulateField;
use Enjin\Platform\Interfaces\PlatformBlockchainTransaction;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Database\WalletService;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use Enjin\Platform\Support\SS58Address;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;

class BatchTransferBalanceMutation extends Mutation implements PlatformBlockchainTransaction, PlatformGraphQlMutation
{
    use HasIdempotencyField;
    use HasSigningAccountField;
    use HasSimulateField;
    use HasSkippableRules;
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
            'name' => 'BatchTransferBalance',
            'description' => __('enjin-platform::mutation.batch_transfer_balance.description'),
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
            'recipients' => [
                'type' => GraphQL::type('[TransferRecipient!]!'),
                'description' => __('enjin-platform::mutation.common.args.recipients'),
            ],
            'continueOnFailure' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::mutation.common.args.continueOnFailure'),
                'defaultValue' => false,
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
        WalletService $walletService
    ): mixed {
        $recipients = collect($args['recipients'])->map(
            function ($recipient) {
                $transferBalanceParams = Arr::get($recipient, 'transferBalanceParams');

                if ($transferBalanceParams === null) {
                    throw new PlatformException(__('enjin-platform::error.set_transfer_balance_params_for_recipient'));
                }

                return [
                    'accountId' => SS58Address::getPublicKey($recipient['account']),
                    'keepAlive' => $transferBalanceParams['keepAlive'],
                    'value' => $transferBalanceParams['value'],
                ];
            }
        );

        $continueOnFailure = $args['continueOnFailure'];
        $encodedData = $serializationService->encode('Batch', static::getEncodableParams(
            recipients: $recipients->toArray(),
            continueOnFailure: $continueOnFailure
        ));

        return $this->storeTransaction($args, $encodedData);
    }

    public static function getEncodableParams(...$params): array
    {
        $serializationService = resolve(SerializationServiceInterface::class);
        $continueOnFailure = Arr::get($params, 'continueOnFailure', false);
        $recipients = Arr::get($params, 'recipients', []);

        $encodedData = collect($recipients)->map(
            fn ($recipient) => $serializationService->encode($recipient['keepAlive'] ? 'TransferKeepAlive' : 'TransferAllowDeath', [
                'dest' => [
                    'Id' => HexConverter::unPrefix($recipient['accountId']),
                ],
                'value' => gmp_init($recipient['value']),
            ])
        );

        return [
            'calls' => $encodedData->toArray(),
            'continueOnFailure' => $continueOnFailure,
        ];
    }

    /**
     * Get the serialization service method name.
     */
    #[Override]
    public function getMethodName(): string
    {
        return 'Batch';
    }

    /**
     * Get the common rules.
     */
    protected function rulesCommon(array $args): array
    {
        return [
            'recipients' => ['array', 'min:1', 'max:250'],
        ];
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        return [];
    }

    /**
     * Get the mutation's validation rules withoud DB rules.
     */
    protected function rulesWithoutValidation(array $args): array
    {
        return [];
    }
}
