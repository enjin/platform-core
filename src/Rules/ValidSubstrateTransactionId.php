<?php

namespace Enjin\Platform\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidSubstrateTransactionId implements ValidationRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!(is_array($value) ? collect($value)->every(fn ($item) => $this->isValidTransactionId($item)) : $this->isValidTransactionId($value))) {
            $fail('enjin-platform::validation.valid_substrate_transaction_id')->translate();
        }
    }

    /**
     * Determine if the value is a valid transaction id.
     */
    protected function isValidTransactionId($value): bool
    {
        return preg_match('/^\d*-\d*$/', (string) $value) >= 1;
    }
}
