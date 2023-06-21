<?php

namespace Enjin\Platform\Rules;

use Illuminate\Contracts\Validation\Rule;

class DistinctAttributes implements Rule
{
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
        return is_array($value) && collect($value)->pluck('key')->unique()->count() === count($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('enjin-platform::validation.distinct_attribute');
    }
}
