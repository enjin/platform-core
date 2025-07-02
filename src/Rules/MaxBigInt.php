<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Support\Hex;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class MaxBigInt implements ValidationRule
{
    /**
     * The validation error message.
     */
    protected string $message;

    /**
     * Create a new rule instance.
     */
    public function __construct(protected string|int $max = Hex::MAX_UINT256) {}

    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!(is_array($value) ? collect($value)->flatten()->every(fn ($item) => $this->isValidMaxBigInt($item)) : $this->isValidMaxBigInt($value))) {
            $fail($this->message)
                ->translate([
                    'max' => $this->max,
                ]);
        }
    }

    /**
     * Determine if the value is a valid max big int.
     */
    protected function isValidMaxBigInt($value): bool
    {
        if (!is_numeric($value)) {
            $this->message = 'validation.numeric';

            return false;
        }

        $this->message = 'enjin-platform::validation.max_big_int';

        return bccomp($this->max, $value) >= 0;
    }
}
