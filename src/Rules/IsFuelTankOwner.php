<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\FuelTanks\Models\FuelTank;
use Enjin\Platform\Rules\Traits\HasDataAwareRule;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class IsFuelTankOwner implements DataAwareRule, ValidationRule
{
    use HasDataAwareRule;

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $fuelTank = FuelTank::where('public_key', SS58Address::getPublicKey($value))
            ->with('owner')
            ->first();
        if (!$fuelTank) {
            $fail(__('validation.exists', ['attribute' => $attribute]))->translate();

            return;
        }

        if (!SS58Address::isSameAddress($fuelTank->owner->public_key, Arr::get($this->data, 'signingAccount') ?? Account::daemonPublicKey())) {
            $fail('enjin-platform::validation.is_fuel_tank_owner')->translate();
        }
    }
}
