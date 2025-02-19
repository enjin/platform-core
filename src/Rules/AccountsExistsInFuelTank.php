<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\FuelTankAccount;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class AccountsExistsInFuelTank implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(protected ?string $account) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->account) {
            return;
        }

        $values = Arr::wrap($value);
        if (FuelTankAccount::byFuelTankAccount($this->account)
            ->byWalletAccount($values)
            ->count() !== count($values)
        ) {
            $fail('enjin-platform::validation.account_exists_in_fuel_tank')->translate();
        }
    }
}
