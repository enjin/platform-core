<?php

namespace Enjin\Platform\Rules;

use Enjin\Platform\Support\Hex;
use Illuminate\Contracts\Validation\Rule;

class MaxBigInt implements Rule
{
    /**
     * The validation error message.
     */
    protected string $message;

    /**
     * Create a new rule instance.
     */
    public function __construct(protected string|int $max = Hex::MAX_UINT256)
    {
    }

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
        if (is_array($value)) {
            return collect($value)->flatten()->every(fn ($item) => $this->isValidMaxBigInt($item));
        }

        return $this->isValidMaxBigInt($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }

    /**
     * Determine if the value is a valid max big int.
     */
    protected function isValidMaxBigInt($value): bool
    {
        if (!is_numeric($value)) {
            $this->message = __('validation.numeric');

            return false;
        }

        $this->message = __('enjin-platform::validation.max_big_int', ['max' => $this->max]);

        return bccomp($this->max, $value) >= 0;
    }
}
