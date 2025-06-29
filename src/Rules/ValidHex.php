<?php

namespace Enjin\Platform\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidHex implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(protected ?int $bytesLength = null) {}

    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!(is_array($value) ? collect($value)->every(fn ($item) => $this->isValidHex($item)) : $this->isValidHex($value))) {
            $fail('enjin-platform::validation.valid_hex')->translate();
        }
    }

    /**
     * Determine if the value is a valid hex.
     */
    protected function isValidHex($value): bool
    {
        if (!is_string($value) || ($this->bytesLength !== null && strlen($value) !== ((2 * $this->bytesLength) + 2))) {
            return false;
        }

        return preg_match('/^0x[a-fA-F0-9]*$/', $value) >= 1;
    }
}
