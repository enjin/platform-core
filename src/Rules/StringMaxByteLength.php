<?php

namespace Enjin\Platform\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StringMaxByteLength implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct($param)
    {
        $this->max = $param;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (filled($value) && (strlen((string) $value) > $this->max)) {
            $fail('enjin-platform::validation.string_too_large')->translate(['attribute' => $attribute]);
        }
    }
}
