<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Collection;
use Illuminate\Contracts\Validation\ValidationRule;

class NoTokensInCollection implements ValidationRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     *
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $collection = Collection::query()->withCount('tokens')->firstWhere('collection_chain_id', '=', $value);

        if (0 !== $collection?->tokens_count) {
            $fail('enjin-platform::validation.no_tokens_in_collection')->translate();
        }
    }
}
