<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Collection;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class NoTokensInCollection implements ValidationRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $collection = Collection::query()->withCount('tokens')->firstWhere('collection_chain_id', '=', $value);

        if ($collection?->tokens_count !== 0) {
            $fail('enjin-platform::validation.no_tokens_in_collection')->translate();
        }
    }
}
