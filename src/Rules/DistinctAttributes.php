<?php

namespace Enjin\Platform\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class DistinctAttributes implements ValidationRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!(is_array($value) && collect($value)->pluck('key')->unique()->count() === count($value))) {
            $fail('enjin-platform::validation.distinct_attribute')->translate();
        }
    }
}
