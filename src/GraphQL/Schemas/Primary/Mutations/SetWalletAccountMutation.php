<?php

namespace Enjin\Platform\GraphQL\Schemas\Primary\Mutations;

use Closure;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\InPrimarySchema;
use Enjin\Platform\Interfaces\PlatformGraphQlMutation;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Enjin\Platform\Services\Database\WalletService;
use Enjin\Platform\Support\SS58Address;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class SetWalletAccountMutation extends Mutation implements PlatformGraphQlMutation
{
    use InPrimarySchema;

    /**
     * Get the mutation's attributes.
     */
    public function attributes(): array
    {
        return [
            'name' => 'SetWalletAccount',
            'description' => __('enjin-platform::mutation.set_wallet_account.description'),
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
    public function args(): array
    {
        return [
            'id' => [
                'type' => GraphQL::type('Int'),
                'description' => __('enjin-platform::query.get_wallet.args.id'),
                'rules' => [
                    'required_without:externalId',
                    function (string $attribute, mixed $value, Closure $fail) {
                        if (!Wallet::where('id', $value)->exists()) {
                            $fail('validation.exists')->translate();
                        }
                    },
                ],
            ],
            'externalId' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform::query.get_wallet.args.externalId'),
                'rules' => [
                    'required_without:id',
                    function (string $attribute, mixed $value, Closure $fail) {
                        if (!Wallet::where('external_id', $value)->exists()) {
                            $fail('validation.exists')->translate();
                        }
                    },
                ],
            ],
            'account' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform::query.get_wallet.args.account'),
                'rules' => ['filled', new ValidSubstrateAccount()],
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
        if (Wallet::firstWhere('public_key', '=', SS58Address::getPublicKey($args['account']))) {
            throw new PlatformException(__('enjin-platform::error.account_already_taken'));
        }

        $column = array_key_first(Arr::except($args, ['account']));
        $wallet = $walletService->get($args[$column], Str::snake($column));

        if ($wallet->public_key) {
            throw new PlatformException(__('enjin-platform::error.wallet_is_immutable'), 403);
        }

        return $walletService->update($wallet, ['public_key' => SS58Address::getPublicKey($args['account'])]);
    }
}
