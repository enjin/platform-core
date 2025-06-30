<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class IsManagedWallet implements ValidationRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!SS58Address::isSameAddress($value, Account::daemonPublicKey()) && !in_array(SS58Address::getPublicKey($value), Account::managedPublicKeys())) {
            $fail('enjin-platform::validation.is_managed_wallet')->translate();
        }
    }
}
