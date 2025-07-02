<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Rules\Traits\HasDataAwareRule;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Translation\PotentiallyTranslatedString;

class MinTokenDeposit implements DataAwareRule, ValidationRule
{
    use HasDataAwareRule;

    /**
     * The minimum token deposit.
     */
    protected int $minTokenDeposit = 10 ** 16;

    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $initialSupply = Arr::get($this->data, str_replace('.unitPrice', '.initialSupply', $attribute));
        $tokenDeposit = gmp_mul($initialSupply, $value);

        if (gmp_sub($tokenDeposit, $this->minTokenDeposit) < 0) {
            $fail('enjin-platform::validation.min_token_deposit')->translate();
        }
    }
}
