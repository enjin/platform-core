<?php

namespace Enjin\Platform\GraphQL\Schemas\FuelTanks\Mutations;

use Closure;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Enums\CoveragePolicy;
use Enjin\Platform\GraphQL\Traits\HasFuelTankValidationRules;
use Enjin\Platform\Models\Substrate\AccountRulesParams;
use Enjin\Platform\Services\Blockchain\Implemetations\Substrate;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\StoresTransactions;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasSkippableRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTransactionDeposit;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasIdempotencyField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSigningAccountField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSimulateField;
use Enjin\Platform\Interfaces\PlatformBlockchainTransaction;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Rebing\GraphQL\Support\Facades\GraphQL;

class CreateFuelTankMutation extends Mutation implements PlatformBlockchainTransaction
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
            'name' => 'CreateFuelTank',
            'description' => __('enjin-platform::mutation.create_fuel_tank.description'),
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
            'name' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::type.fuel_tank.field.name'),
            ],
            'reservesAccountCreationDeposit' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::type.fuel_tank.field.reservesAccountCreationDeposit'),
            ],
            'coveragePolicy' => [
                'type' => GraphQL::type('CoveragePolicy'),
                'description' => __('enjin-platform::type.fuel_tank.field.coveragePolicy'),
                'defaultValue' => CoveragePolicy::FEES->name,
            ],
            'accountRules' => [
                'type' => GraphQL::type('AccountRuleInputType'),
                'description' => __('enjin-platform::input_type.account_rule.description'),
            ],
            'dispatchRules' => [
                'type' => GraphQL::type('[DispatchRuleInputType!]'),
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
            // Deprecated fields, they don't exist on-chain anymore, should be removed at 2.1.0
            'reservesExistentialDeposit' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::type.fuel_tank.field.reservesExistentialDeposit'),
                'deprecationReason' => __('enjin-platform::deprecated.fuel_tank.field.reservesExistentialDeposit'),
            ],
            'providesDeposit' => [
                'type' => GraphQL::type('Boolean'),
                'description' => __('enjin-platform::type.fuel_tank.field.providesDeposit'),
                'deprecationReason' => __('enjin-platform::deprecated.fuel_tank.field.providesDeposit'),
            ],
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
        $dispatchRules = $blockchainService->getDispatchRulesParamsArray($args);
        $encodedData = $serializationService->encode($this->getMutationName(), static::getEncodableParams(
            name: $args['name'],
            userAccountManagement: $blockchainService->getUserAccountManagementParams($args),
            dispatchRules: $dispatchRules,
            requireAccount: $args['requireAccount'],
            coveragePolicy: $args['coveragePolicy'] ?? CoveragePolicy::FEES,
            accountRules: $blockchainService->getAccountRulesParams($args)
        ));

        $encodedData = self::addPermittedExtrinsics($encodedData, $dispatchRules);

        return Transaction::lazyLoadSelectFields(
            DB::transaction(fn () => $this->storeTransaction($args, $encodedData)),
            $resolveInfo
        );
    }

    public static function addPermittedExtrinsics(string $encodedData, array $dispatchRules): string
    {
        if (empty($dispatchRules) || $dispatchRules[0]->permittedExtrinsics === null) {
            return $encodedData;
        }

        $splitData = preg_split('/0700(0[01]0[01])/', $encodedData, -1, PREG_SPLIT_DELIM_CAPTURE);

        $permittedExtrinsics = Arr::get($dispatchRules[0]->permittedExtrinsics->toEncodable(), 'PermittedExtrinsics.extrinsics');

        return $splitData[0] . $permittedExtrinsics . $splitData[1] . $splitData[2];
    }

    #[\Override]
    public static function getEncodableParams(...$params): array
    {
        $name = Arr::get($params, 'name', '');
        $userAccountManagement = Arr::get($params, 'userAccountManagement');
        $ruleSets = collect(Arr::get($params, 'dispatchRules', []));
        $coveragePolicy = is_string($coverage = Arr::get($params, 'coveragePolicy')) ? CoveragePolicy::getEnumCase($coverage) : $coverage;
        $accountRules = Arr::get($params, 'accountRules', new AccountRulesParams());
        $requireAccount = Arr::get($params, 'requireAccount', false);

        return [
            'descriptor' => [
                'name' => HexConverter::stringToHexPrefixed($name),
                'userAccountManagement' => $userAccountManagement?->toEncodable(),
                'coveragePolicy' => $coveragePolicy?->value ?? CoveragePolicy::FEES->value,
                'ruleSets' =>  [
                    [
                        'rules' => $ruleSets->flatMap(fn ($ruleSet) => $ruleSet->toEncodable())->all(),
                        'requireAccount' => $requireAccount,
                    ],
                ],
                'accountRules' => $accountRules?->toEncodable() ?? [],
            ],
        ];
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        return $this->validationRulesExist($args);
    }

    /**
     * Get the mutation's validation rules without DB rules.
     */
    protected function rulesWithoutValidation(array $args): array
    {
        return $this->validationRules($args);
    }
}
