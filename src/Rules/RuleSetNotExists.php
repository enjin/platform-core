<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\FuelTanks\Models\FuelTank;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class RuleSetNotExists implements DataAwareRule, ValidationRule
{
    /**
     * The data being validated.
     */
    protected array $data;

    /**
     * Set the data being validated.
     */
    public function setData(mixed $data): void
    {
        $this->data = $data;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->data['tankId'] && FuelTank::whereHas(
            'dispatchRules',
            fn ($query) => $query->where('rule_set_id', $value)
        )->where('public_key', SS58Address::getPublicKey($this->data['tankId']))->exists()
        ) {
            $fail('enjin-platform-fuel-tanks::validation.rule_set_exist')->translate();
        }
    }
}
