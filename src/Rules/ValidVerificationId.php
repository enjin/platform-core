<?php

namespace Enjin\Platform\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidVerificationId implements ValidationRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || strlen($value) !== 66 || preg_match('/^0x[a-fA-F0-9]*$/', $value) < 1) {
            $fail('enjin-platform::validation.valid_verification_id')->translate();
        }
    }
}
