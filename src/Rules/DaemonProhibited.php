<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Laravel\Wallet;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\ValidationRule;

class DaemonProhibited implements ValidationRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $wallet = is_int($value) ? Wallet::find($value)?->address : $value;

        if (isset($wallet) && SS58Address::isSameAddress($wallet, Account::daemonPublicKey())) {
            $fail('enjin-platform::validation.daemon_prohibited')->translate();
        }
    }
}
