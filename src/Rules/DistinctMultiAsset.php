<?php

namespace Enjin\Platform\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DistinctMultiAsset implements ValidationRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     *
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!(is_array($value) && collect($value)->unique()->count() === count($value))) {
            $fail('enjin-platform::validation.distinct_multi_asset')->translate();
        }
    }
}
