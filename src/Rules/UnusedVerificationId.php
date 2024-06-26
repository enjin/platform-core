<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Wallet;
use Illuminate\Contracts\Validation\ValidationRule;

class UnusedVerificationId implements ValidationRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (Wallet::where('verification_id', $value)->exists()) {
            $fail(__('enjin-platform::validation.unused_verification_id'));
        }
    }
}
