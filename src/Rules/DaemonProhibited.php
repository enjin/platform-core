<?php

namespace Enjin\Platform\Rules;

use Enjin\Platform\Models\Laravel\Wallet;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\Rule;

class DaemonProhibited implements Rule
{
    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value)
    {
        if (is_int($value)) {
            if (!($wallet = Wallet::find($value))) {
                return true;
            }

            return !SS58Address::isSameAddress($wallet->address, config('enjin-platform.chains.daemon-account'));
        }

        return !SS58Address::isSameAddress($value, config('enjin-platform.chains.daemon-account'));
    }

    /**
     * Get the validation error message.
     */
    public function message()
    {
        return __('enjin-platform::validation.daemon_prohibited');
    }
}
