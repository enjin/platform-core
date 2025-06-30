<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate\Traits;

use Enjin\Platform\Models\Indexer\Account;
use Enjin\Platform\Rules\NotDaemonWallet;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Support\Arr;
use Rebing\GraphQL\Support\Facades\GraphQL;

trait HasSigningAccountField
{
    /**
     * Get the signing account field.
     */
    public function getSigningAccountField(
        ?string $signingAccountDesc = null,
    ): array {
        $signingAccountType = [
            'type' => GraphQL::type('String'),
            'description' => $signingAccountDesc ?: __('enjin-platform::mutation.args.signingAccount'),
            'rules' => ['bail', 'nullable', new ValidSubstrateAccount(), new NotDaemonWallet()],
        ];

        return [
            'signingAccount' => $signingAccountType,
        ];
    }

    /**
     * Get the signing account.
     */
    public function getSigningAccount(array $args): ?Account
    {
        if (!empty($signing = Arr::get($args, 'signingAccount'))) {
            return Account::find(SS58Address::getPublicKey($signing));
            // TODO: Check this
            //            return WalletService::firstOrStore([
            //                'account' => $signing,
            //            ]);
        }

        return null;
    }
}
