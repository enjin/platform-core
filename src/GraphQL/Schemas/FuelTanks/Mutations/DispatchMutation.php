<?php

namespace Enjin\Platform\GraphQL\Schemas\FuelTanks\Mutations;

use Closure;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Enums\Substrate\DispatchCall;
use Enjin\Platform\Exceptions\FuelTanksException;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\StoresTransactions;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasSkippableRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTransactionDeposit;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasIdempotencyField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSigningAccountField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSimulateField;
use Enjin\Platform\Interfaces\PlatformBlockchainTransaction;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\CanDispatch;
use Enjin\Platform\Rules\FuelTankExists;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Rules\RuleSetExists;
use Enjin\Platform\Rules\ValidHex;
use Enjin\Platform\Rules\ValidMutation;
use Enjin\Platform\Rules\ValidSubstrateAddress;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Support\SS58Address;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Rebing\GraphQL\Support\Facades\GraphQL;

class DispatchMutation extends FuelTanksMutation implements PlatformBlockchainTransaction
{
    use HasIdempotencyField;
    use HasSigningAccountField;
    use HasSimulateField;
    use HasSkippableRules;
    use HasTransactionDeposit;
    use StoresTransactions;

    /**
     * Get the mutation's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'Dispatch',
            'description' => __('enjin-platform::mutation.dispatch.description'),
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
            'tankId' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::mutation.fuel_tank.args.tankId'),
            ],
            'ruleSetId' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::mutation.dispatch.args.ruleSetId'),
            ],
            'dispatch' => [
                'type' => GraphQL::type('DispatchInputType!'),
                'description' => __('enjin-platform::input_type.dispatch.description'),
            ],
            'paysRemainingFee' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::mutation.dispatch.args.paysRemainingFee'),
                'deprecationReason' => __('enjin-platform::deprecation.dispatch.args.paysRemainingFee'),
            ],
            ...$this->getSigningAccountField(),
            ...$this->getIdempotencyField(),
            ...$this->getSimulateField(),
            ...$this->getSkipValidationField(),
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
    ) {
        DB::beginTransaction();
        $encodedCall = static::getFuelTankCall($this->getMethodName(), $args);
        $transaction = $this->storeTransaction($args, $encodedCall);
        DB::commit();

        return Transaction::lazyLoadSelectFields($transaction, $resolveInfo);
    }

    public static function getEncodedCall($args)
    {
        $result = GraphQL::queryAndReturnResult(
            Arr::get($args, 'dispatch.query'),
            (array) Arr::get($args, 'dispatch.variables'),
            ['schema' => DispatchCall::getEnumCase(Arr::get($args, 'dispatch.call'))?->value]
        )->toArray();

        if (Arr::get($result, 'errors.0.message')) {
            throw new FuelTanksException(__('enjin-platform::exception.dispatch_query_error'));
        }

        $encodedData = null;
        if ($data = Arr::get($result, 'data')) {
            $data = array_shift($data);
            $encodedData = Arr::get($data, 'encodedData');
            Transaction::destroy(Arr::get($data, 'id'));
        }

        return HexConverter::unPrefix($encodedData);
    }

    public static function getFuelTankCall($method, $args, ?string $rawCall = null): string
    {
        $paysRemainingFee = Arr::get($args, 'dispatch.settings.paysRemainingFee') ?? Arr::get($args, 'paysRemainingFee');
        $signature = Arr::get($args, 'dispatch.settings.signature.signature');
        $encodedCall = TransactionSerializer::encode($method, static::getEncodableParams(
            tankId: $args['tankId'],
            ruleSetId: $args['ruleSetId'],
        ));

        $encodedCall .= $rawCall ?: static::getEncodedCall($args);

        return $encodedCall . TransactionSerializer::encodeRaw(
            'OptionDispatchSettings',
            ['option' => $paysRemainingFee === null ? null :
                [
                    'useNoneOrigin' => false,
                    'paysRemainingFee' => $paysRemainingFee,
                    'signature' => $signature === null ? null : [
                        'signature' => HexConverter::hexToBytes($signature),
                        'expiryBlock' => Arr::get($args, 'dispatch.settings.signature.expiryBlock'),
                    ],
                ],
            ],
        );
    }

    #[\Override]
    public static function getEncodableParams(...$params): array
    {
        $tankId = Arr::get($params, 'tankId', Account::daemonPublicKey());
        $ruleSetId = Arr::get($params, 'ruleSetId', 0);

        return [
            'tankId' => [
                'Id' => HexConverter::unPrefix(SS58Address::getPublicKey($tankId)),
            ],
            'ruleSetId' => $ruleSetId,
        ];
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        return [
            'tankId' => [
                'bail',
                'filled',
                'max:255',
                new ValidSubstrateAddress(),
                new FuelTankExists(),
            ],
            'ruleSetId' => [
                'bail',
                new MinBigInt(),
                new MaxBigInt(Hex::MAX_UINT32),
                new RuleSetExists(),
            ],
            'dispatch' => [
                new CanDispatch(),
            ],
            'dispatch.query' => [
                'filled',
                new ValidMutation(),
            ],
            'dispatch.settings.signature.signature' => [
                'bail', 'filled', new ValidHex(64),
            ],
            'dispatch.settings.signature.expiryBlock' => [
                'bail', 'integer', 'min:0',
            ],
        ];
    }

    /**
     * Get the mutation's validation rules without DB rules.
     */
    protected function rulesWithoutValidation(array $args): array
    {
        return [
            'tankId' => [
                'bail',
                'filled',
                'max:255',
                new ValidSubstrateAddress(),
            ],
            'ruleSetId' => [
                'bail',
                new MinBigInt(),
                new MaxBigInt(Hex::MAX_UINT32),
            ],
            'dispatch.query' => [
                'filled',
                new ValidMutation(),
            ],
            'dispatch.settings.signature.signature' => [
                'bail', 'filled', new ValidHex(64),
            ],
            'dispatch.settings.signature.expiryBlock' => [
                'bail', 'integer', 'min:0',
            ],
        ];
    }
}
