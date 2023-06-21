<?php

namespace Enjin\Platform\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidRoyaltyPercentage implements Rule
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
        if (!is_float($value) || $value < 0.1 || $value > 50) {
            return false;
        }

        return preg_match('/^(\d*\.)?\d{0,7}$/', $value) >= 1;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('enjin-platform::validation.valid_royalty_percentage');
    }
}
