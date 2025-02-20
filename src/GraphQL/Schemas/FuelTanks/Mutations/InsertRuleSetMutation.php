<?php

namespace Enjin\Platform\GraphQL\Schemas\FuelTanks\Mutations;

use Closure;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\GraphQL\Schemas\FuelTanks\Queries\HasFuelTankValidationRules;
use Enjin\Platform\Models\Substrate\DispatchRulesParams;
use Enjin\Platform\Rules\IsFuelTankOwner;
use Enjin\Platform\Services\Blockchain\Implemetations\Substrate;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\StoresTransactions;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasSkippableRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTransactionDeposit;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasIdempotencyField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSigningAccountField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSimulateField;
use Enjin\Platform\Interfaces\PlatformBlockchainTransaction;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Rules\ValidSubstrateAddress;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Support\SS58Address;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;

class InsertRuleSetMutation extends Mutation implements PlatformBlockchainTransaction
{
    use HasFuelTankValidationRules;
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
            'name' => 'InsertRuleSet',
            'description' => __('enjin-platform::mutation.insert_rule_set.description'),
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
                'description' => __('enjin-platform::mutation.fuel_tank.args.ruleSetId'),
            ],
            'dispatchRules' => [
                'type' => GraphQL::type('DispatchRuleInputType!'),
                'description' => __('enjin-platform::input_type.dispatch_rule.description'),
            ],
            'requireAccount' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::mutation.insert_rule_set.args.requireAccount'),
                'defaultValue' => false,
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
        SerializationServiceInterface $serializationService,
        Substrate $blockchainService
    ) {
        $dispatchRules = $blockchainService->getDispatchRulesParams($args['dispatchRules']);
        $encodedData = $serializationService->encode(
            $this->getMutationName(),
            static::getEncodableParams(
                tankId: $args['tankId'],
                ruleSetId: $args['ruleSetId'],
                dispatchRules: $dispatchRules,
                requireAccount: $args['requireAccount'],
            )
        );

        if (Arr::get($args, 'dispatchRules.permittedExtrinsics')) {
            $requireAccount = Str::take($encodedData, -2);
            $encodedData = Str::take($encodedData, Str::length($encodedData) - 6);
            $encodedData .= Arr::get($dispatchRules->permittedExtrinsics->toEncodable(), 'PermittedExtrinsics.extrinsics');
            $encodedData .= $requireAccount; // This adds requireAccount back
        }

        return Transaction::lazyLoadSelectFields(
            DB::transaction(fn () => $this->storeTransaction($args, $encodedData)),
            $resolveInfo
        );
    }

    #[\Override]
    public static function getEncodableParams(...$params): array
    {
        $tankId = Arr::get($params, 'tankId', Account::daemonPublicKey());
        $ruleSetId = Arr::get($params, 'ruleSetId', 0);
        $rules = Arr::get($params, 'dispatchRules', new DispatchRulesParams())->toEncodable();
        $requireAccount = Arr::get($params, 'requireAccount', false);

        return [
            'tankId' => [
                'Id' => HexConverter::unPrefix(SS58Address::getPublicKey($tankId)),
            ],
            'ruleSetId' => $ruleSetId,
            'ruleSet' => [
                'rules' => $rules,
                'requireAccount' => $requireAccount,
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
            ],
            ...$this->dispatchRulesExist($args, '', false),
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
            ...$this->dispatchRules($args, '', false),
        ];
    }
}
