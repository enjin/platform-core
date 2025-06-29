<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Indexer\FuelTank;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

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
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->data['tankId'] && FuelTank::whereHas(
            'dispatchRules',
            fn ($query) => $query->where('rule_set_id', $value)
        )->where('public_key', SS58Address::getPublicKey($this->data['tankId']))->exists()
        ) {
            $fail('enjin-platform::validation.rule_set_exist')->translate();
        }
    }
}
