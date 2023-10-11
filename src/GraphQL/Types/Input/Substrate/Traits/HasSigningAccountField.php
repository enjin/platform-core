<?php

namespace Enjin\Platform\GraphQL\Types\Input\Substrate\Traits;

use Enjin\Platform\Rules\ValidSubstrateAccount;
use Rebing\GraphQL\Support\Facades\GraphQL;

trait HasSigningAccountField
{
    /**
     * Get the idempotency field.
     */
    public function getSigningAccountField(
        ?string $signingAccountDesc = null,
    ): array {
        $signingAccountType = [
            'type' => GraphQL::type('String'),
            'description' => $signingAccountDesc ?: __('enjin-platform::args.signingAccount'),
            'rules' => ['nullable', new ValidSubstrateAccount()],
        ];

        return [
            'signingAccount' => $signingAccountType,
        ];
    }
}
