<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Mutations;

use Closure;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\GraphQL\Middleware\SingleFilterOnly;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\InPrimarySchema;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Rules\AccountExistsInWallet;
use Enjin\Platform\Rules\DaemonProhibited;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Enjin\Platform\Services\Database\WalletService;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class UpdateWalletExternalIdMutation extends Mutation implements PlatformGraphQlMutation
{
    use InPrimarySchema;

    protected $middleware = [
        SingleFilterOnly::class,
    ];

    /**
     * Get the mutation's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'UpdateWalletExternalId',
            'description' => __('enjin-platform::mutation.update_external_id.description'),
        ];
    }

    /**
     * Get the mutation's return type.
     */
    public function type(): Type
    {
        return GraphQL::type('Boolean!');
    }

    /**
     * Get the mutation's arguments definition.
     */
    #[\Override]
    public function args(): array
    {
        return [
            'id' => [
                'type' => GraphQL::type('Int'),
                'description' => __('enjin-platform::query.get_wallet.args.id'),
                'rules' => [
                    'required_without_all:externalId,account',
                    'nullable',
                    'bail',
                    function (string $attribute, mixed $value, Closure $fail): void {
                        if (!Wallet::where('id', $value)->exists()) {
                            $fail('validation.exists')->translate();
                        }
                    },
                    new DaemonProhibited(),
                ],
                'singleFilter' => true,
            ],
            'externalId' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::query.get_wallet.args.externalId'),
                'rules' => [
                    'required_without_all:id,account',
                    'nullable',
                    function (string $attribute, mixed $value, Closure $fail): void {
                        if (!Wallet::where('external_id', $value)->exists()) {
                            $fail('validation.exists')->translate();
                        }
                    },
                ],
                'singleFilter' => true,
            ],
            'newExternalId' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::query.get_wallet.args.newExternalId'),
                'rules' => ['unique:wallets,external_id'],
            ],
            'account' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::query.get_wallet.args.account'),
                'rules' => ['required_without_all:id,externalId', 'nullable', 'bail', new ValidSubstrateAccount(), new DaemonProhibited(), new AccountExistsInWallet()],
                'singleFilter' => true,
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
        WalletService $walletService
    ): mixed {
        $column = array_key_first(Arr::except(array_filter($args), ['newExternalId']));
        $wallet = $walletService->get($args[$column], Str::snake($column));

        if ($wallet->managed) {
            throw new PlatformException(__('enjin-platform::mutation.update_wallet_external_id.cannot_update_id_on_managed_wallet'), 403);
        }

        return $walletService->update($wallet, ['external_id' => $args['newExternalId'] ?: null]);
    }
}
