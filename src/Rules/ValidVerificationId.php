<?php

namespace Enjin\Platform\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidVerificationId implements Rule
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
        if (!is_string($value) || 66 !== strlen($value)) {
            return false;
        }

        return preg_match('/^0x[a-fA-F0-9]*$/', $value) >= 1;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('enjin-platform::validation.valid_verification_id');
    }
}
