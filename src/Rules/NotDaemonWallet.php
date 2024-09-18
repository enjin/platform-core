<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\ValidationRule;

class NotDaemonWallet implements ValidationRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (SS58Address::getPublicKey($value) === Account::daemonPublicKey()) {
            $fail('enjin-platform::validation.not_daemon_wallet')->translate();
        }
    }
}
