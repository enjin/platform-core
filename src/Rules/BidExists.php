<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Bid;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class BidExists implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    #[\Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!Bid::where('id', $value)->exists()) {
            $fail('validation.exists')->translate();
        }
    }
}
