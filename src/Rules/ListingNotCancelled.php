<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Enums\Substrate\ListingState;
use Enjin\Platform\Models\Listing;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ListingNotCancelled implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    #[\Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$listing = Listing::where('listing_chain_id', $value)->with('state')->first()) {
            $fail('validation.exists')->translate();

            return;
        }

        if ($listing->state?->state === ListingState::CANCELLED->name) {
            $fail('enjin-platform::validation.listing_not_cancelled')->translate();
        }
    }
}
