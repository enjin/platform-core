<?php

namespace Enjin\Platform\Rules;

use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\Rule;

class IsManagedWallet implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (SS58Address::isSameAddress($value, Account::daemonPublicKey())) {
            return true;
        }

        return in_array(SS58Address::getPublicKey($value), Account::managedPublicKeys());
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('enjin-platform::validation.is_managed_wallet');
    }
}
