<?php

namespace Enjin\Platform\Rules;

use Illuminate\Contracts\Validation\Rule;

class MinBigInt implements Rule
{
    /**
     * The validation error message.
     */
    protected string $message;

    /**
     * Create a new rule instance.
     */
    public function __construct(protected string|int $min = 0)
    {
        $this->min = $min;
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
            return collect($value)->flatten()->every(fn ($item) => $this->isValidMinBigInt($item));
        }

        return $this->isValidMinBigInt($value);
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
     * Determine if the value is a valid min big int.
     */
    protected function isValidMinBigInt($value): bool
    {
        if (!is_numeric($value)) {
            $this->message = __('validation.numeric');

            return false;
        }

        $this->message = __('enjin-platform::validation.min_big_int', ['min' => $this->min]);

        return bccomp($this->min, $value) <= 0;
    }
}
