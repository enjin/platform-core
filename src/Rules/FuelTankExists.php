<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\FuelTank;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class FuelTankExists implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!FuelTank::where('public_key', SS58Address::getPublicKey($value))->exists()) {
            $fail('validation.exists')->translate(['attribute' => $attribute]);
        }
    }
}
