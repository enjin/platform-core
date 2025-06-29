<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Indexer\TankUserAccount;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Translation\PotentiallyTranslatedString;

class AccountsNotInFuelTank implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(protected ?string $account) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->account) {
            return;
        }

        if (TankUserAccount::byFuelTankAccount($this->account)->byWalletAccount(Arr::wrap($value))->exists()) {
            $fail('enjin-platform::validation.accounts_not_in_fuel_tank')->translate();
        }
    }
}
