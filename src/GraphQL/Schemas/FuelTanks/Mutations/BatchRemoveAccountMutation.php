<?php

namespace Enjin\Platform\GraphQL\Schemas\FuelTanks\Mutations;

use Closure;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Rules\AccountsExistsInFuelTank;
use Enjin\Platform\Rules\IsFuelTankOwner;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\StoresTransactions;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasSkippableRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTransactionDeposit;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasIdempotencyField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSigningAccountField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSimulateField;
use Enjin\Platform\Interfaces\PlatformBlockchainTransaction;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\ValidSubstrateAddress;
use Enjin\Platform\Services\Serialization\Interfaces\SerializationServiceInterface;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\SS58Address;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Rebing\GraphQL\Support\Facades\GraphQL;

class BatchRemoveAccountMutation extends FuelTanksMutation implements PlatformBlockchainTransaction
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
            'name' => 'BatchRemoveAccount',
            'description' => __('enjin-platform::mutation.batch_remove_account.description'),
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
            'userIds' => [
                'type' => GraphQL::type('[String!]!'),
                'description' => __('enjin-platform::mutation.batch_remove_account.args.userIds'),
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
        SerializationServiceInterface $serializationService
    ) {
        $encodedData = $serializationService->encode($this->getMutationName(), static::getEncodableParams(...$args));

        return Transaction::lazyLoadSelectFields(
            DB::transaction(fn () => $this->storeTransaction($args, $encodedData)),
            $resolveInfo
        );
    }

    #[\Override]
    public static function getEncodableParams(...$params): array
    {
        $tankId = Arr::get($params, 'tankId', Account::daemonPublicKey());
        $userIds = collect(Arr::get($params, 'userIds', []));

        return [
            'tankId' => [
                'Id' => HexConverter::unPrefix(SS58Address::getPublicKey($tankId)),
            ],
            'userIds' => $userIds->map(fn ($userId) => [
                'Id' => HexConverter::unPrefix(SS58Address::getPublicKey($userId)),
            ])->toArray(),
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
            'userIds.*' => ['bail', 'distinct', 'max:255', new ValidSubstrateAddress()],
            'userIds' => [
                'bail',
                'array',
                'min:1',
                'max:100',
                new AccountsExistsInFuelTank(Arr::get($args, 'tankId')),
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
            'userIds.*' => ['bail', 'distinct', 'max:255', new ValidSubstrateAddress()],
            'userIds' => [
                'bail',
                'array',
                'min:1',
                'max:100',
            ],
        ];
    }
}
