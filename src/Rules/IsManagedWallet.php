<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\ValidationRule;

class IsManagedWallet implements ValidationRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     *
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!SS58Address::isSameAddress($value, Account::daemonPublicKey()) && !in_array(SS58Address::getPublicKey($value), Account::managedPublicKeys())) {
            $fail('enjin-platform::validation.is_managed_wallet')->translate();
        }
    }
}
