<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\FuelTanks\Models\FuelTank;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\ValidationRule;

class FuelTankExists implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!FuelTank::where('public_key', SS58Address::getPublicKey($value))->exists()) {
            $fail('validation.exists')->translate(['attribute' => $attribute]);
        }
    }
}
