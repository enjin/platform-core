<?php

namespace Enjin\Platform\GraphQL\Schemas\FuelTanks\Mutations;

use Closure;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\StoresTransactions;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasSkippableRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTransactionDeposit;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasIdempotencyField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSigningAccountField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSimulateField;
use Enjin\Platform\Interfaces\PlatformBlockchainTransaction;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\AccountsExistsInFuelTank;
use Enjin\Platform\Rules\IsFuelTankOwner;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Rules\RuleSetExists;
use Enjin\Platform\Rules\ValidSubstrateAddress;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Support\SS58Address;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use Illuminate\Support\Facades\DB;
use Override;
use Rebing\GraphQL\Support\Facades\GraphQL;

class ForceSetConsumptionMutation extends FuelTanksMutation implements PlatformBlockchainTransaction
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
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'SetConsumption',
            'description' => __('enjin-platform::mutation.force_set_consumption.description'),
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
            'tankId' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::mutation.fuel_tank.args.tankId'),
            ],
            'ruleSetId' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::mutation.fuel_tank.args.ruleSetId'),
            ],
            'userId' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::mutation.force_set_consumption.args.userId'),
            ],
            'totalConsumed' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform::mutation.force_set_consumption.args.totalConsumed'),
            ],
            'lastResetBlock' => [
                'type' => GraphQL::type('Int'),
                'description' => __('enjin-platform::mutation.force_set_consumption.args.lastResetBlock'),
            ],
            ...$this->getSigningAccountField(),
            ...$this->getIdempotencyField(),
            ...$this->getSimulateField(),
            ...$this->getSkipValidationField(),
        ];
    }

    #[Override]
    public function getMethodName(): string
    {
        return 'ForceSetConsumption';
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
        SerializationServiceInterface $serializationService
    ) {
        $encodedData = $serializationService->encode($this->getMethodName(), static::getEncodableParams(...$args));

        return Transaction::lazyLoadSelectFields(
            DB::transaction(fn () => $this->storeTransaction($args, $encodedData)),
            $resolveInfo
        );
    }

    #[Override]
    public static function getEncodableParams(...$params): array
    {
        $tankId = Arr::get($params, 'tankId', Account::daemonPublicKey());
        $userId = Arr::get($params, 'userId', null);
        $ruleSetId = Arr::get($params, 'ruleSetId', 0);
        $totalConsumed = Arr::get($params, 'totalConsumed', 0);
        $lastResetBlock = Arr::get($params, 'lastResetBlock', null);

        return [
            'tankId' => [
                'Id' => HexConverter::unPrefix(SS58Address::getPublicKey($tankId)),
            ],
            'userId' => $userId ? [
                'Id' => HexConverter::unPrefix(SS58Address::getPublicKey($userId)),
            ] : null,
            'ruleSetId' => $ruleSetId,
            'consumption' => [
                'totalConsumed' => $totalConsumed,
                'lastResetBlock' => $lastResetBlock,
            ],
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
                new IsFuelTankOwner(),
            ],
            'ruleSetId' => [
                'bail',
                new MinBigInt(),
                new MaxBigInt(Hex::MAX_UINT32),
                new RuleSetExists(),
            ],
            'userId' => [
                'nullable',
                'bail',
                'max:255',
                new ValidSubstrateAddress(),
                new AccountsExistsInFuelTank(Arr::get($args, 'tankId')),
            ],
            'totalConsumed' => [
                'bail',
                new MinBigInt(),
                new MaxBigInt(),
            ],
            'lastResetBlock' => [
                'nullable',
                'bail',
                new MinBigInt(),
                new MaxBigInt(Hex::MAX_UINT32),
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
            'userId' => [
                'nullable',
                'bail',
                'max:255',
                new ValidSubstrateAddress(),
            ],
            'totalConsumed' => [
                'bail',
                new MinBigInt(),
                new MaxBigInt(),
            ],
            'lastResetBlock' => [
                'nullable',
                'bail',
                new MinBigInt(),
                new MaxBigInt(Hex::MAX_UINT32),
            ],
        ];
    }
}
