<?php

namespace Enjin\Platform\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidHex implements Rule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(protected ?int $bytesLength = null)
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
            return collect($value)->every(fn ($item) => $this->isValidHex($item));
        }

        return $this->isValidHex($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('enjin-platform::validation.valid_hex');
    }

    /**
     * Determine if the value is a valid hex.
     */
    protected function isValidHex($value): bool
    {
        if (!is_string($value) || (null !== $this->bytesLength && strlen($value) !== ((2 * $this->bytesLength) + 2))) {
            return false;
        }

        return preg_match('/^0x[a-fA-F0-9]*$/', $value) >= 1;
    }
}
