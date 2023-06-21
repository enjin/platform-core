<?php

namespace Enjin\Platform\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;

class MinTokenDeposit implements DataAwareRule, Rule
{
    /**
     * All of the data under validation.
     */
    protected array $data = [];

    /**
     * The minimum token deposit.
     */
    protected int $minTokenDeposit = 10 ** 16;

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $initialSupply = Arr::get($this->data, str_replace('.unitPrice', '.initialSupply', $attribute));
        $tokenDeposit = gmp_mul($initialSupply, $value);

        return gmp_sub($tokenDeposit, $this->minTokenDeposit) >= 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('enjin-platform::validation.min_token_deposit');
    }

    /**
     * Set the data under validation.
     *
     * @param array $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }
}
