<?php

namespace Enjin\Platform\Rules;

use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\Rule;

class ValidSubstrateAccount implements Rule
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
        ray($value);
        if (is_array($value)) {
            return collect($value)->every(fn ($item) => $this->isValidAddress($item));
        }

        return $this->isValidAddress($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('enjin-platform::validation.valid_substrate_account');
    }

    /**
     * Determine if the value is a valid address.
     */
    protected function isValidAddress($value): bool
    {
        return SS58Address::isValidAddress($value);
    }
}
