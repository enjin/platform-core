<?php

namespace Enjin\Platform\Rules;

use Enjin\Platform\Models\Collection;
use Illuminate\Contracts\Validation\Rule;

class NoTokensInCollection implements Rule
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
        $collection = Collection::query()->withCount('tokens')->firstWhere('collection_chain_id', '=', $value);

        return 0 === $collection?->tokens_count;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('enjin-platform::validation.no_tokens_in_collection');
    }
}
