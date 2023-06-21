<?php

namespace Enjin\Platform\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidSubstrateTransactionId implements Rule
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
        if (is_array($value)) {
            return collect($value)->every(fn ($item) => $this->isValidTransactionId($item));
        }

        return $this->isValidTransactionId($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('enjin-platform::validation.valid_substrate_transaction_id');
    }

    /**
     * Determine if the value is a valid transaction id.
     */
    protected function isValidTransactionId($value): bool
    {
        return preg_match('/^\d*-\d*$/', $value) >= 1;
    }
}
