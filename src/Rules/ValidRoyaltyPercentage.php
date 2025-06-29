<?php

namespace Enjin\Platform\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidRoyaltyPercentage implements ValidationRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_float($value) || $value < 0.1 || $value > 50 || preg_match('/^(\d*\.)?\d{0,7}$/', $value) < 1) {
            $fail('enjin-platform::validation.valid_royalty_percentage')->translate();
        }
    }
}
