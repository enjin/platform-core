<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidSubstrateAccount implements ValidationRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!(is_array($value) ? collect($value)->every(fn ($item) => $this->isValidAddress($item)) : $this->isValidAddress($value))) {
            $fail('enjin-platform::validation.valid_substrate_account')->translate();
        }
    }

    /**
     * Determine if the value is a valid address.
     */
    protected function isValidAddress($value): bool
    {
        return SS58Address::isValidAddress($value);
    }
}
