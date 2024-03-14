<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Laravel\Collection;
use Illuminate\Contracts\Validation\ValidationRule;

class CollectionHasTokens implements ValidationRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $collection = Collection::withCount('tokens')->firstWhere(['collection_chain_id' => $value]);
        if (!$collection) {
            $fail('validation.exists')->translate();

            return;
        }

        if (!$collection->tokens_count) {
            $fail('enjin-platform::validation.collection_has_tokens')->translate();
        }
    }
}
