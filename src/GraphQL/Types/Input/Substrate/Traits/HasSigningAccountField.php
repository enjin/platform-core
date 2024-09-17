<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate\Traits;

use Enjin\Platform\Rules\NotDaemonWallet;
use Enjin\Platform\Rules\ValidSubstrateAccount;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Database\Eloquent\Model;
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
    public function getSigningAccount(array $args): ?Model
    {
        if (!empty($signing = Arr::get($args, 'signingAccount'))) {
            return WalletService::firstOrStore([
                'account' => $signing,
            ]);
        }

        return null;
    }
}
