<?php

namespace Enjin\Platform\Rules;

use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\Rule;

class ValidSubstrateAddress implements Rule
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
        return SS58Address::isValidAddress($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('enjin-platform::validation.valid_substrate_address');
    }
}
