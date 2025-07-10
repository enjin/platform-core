<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Support\Address;
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
        if (!SS58Address::isSameAddress($value, Address::daemonPublicKey()) && !in_array(SS58Address::getPublicKey($value), Address::managedPublicKeys())) {
            $fail('enjin-platform::validation.is_managed_wallet')->translate();
        }
    }
}
