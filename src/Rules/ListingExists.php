<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Indexer\Listing;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ListingExists implements ValidationRule
{
    public function __construct(protected string $column = 'listing_chain_id') {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    #[\Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!Listing::where($this->column, $value)->exists()) {
            $fail('validation.exists')->translate();
        }
    }
}
