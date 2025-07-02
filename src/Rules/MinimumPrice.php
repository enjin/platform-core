<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Indexer\Listing;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Translation\PotentiallyTranslatedString;
use Override;

class MinimumPrice implements DataAwareRule, ValidationRule
{
    /**
     * All the data under validation.
     */
    protected array $data = [];

    /**
     * Set the data under validation.
     */
    #[Override]
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    #[Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($listingId = Arr::get($this->data, 'listingId')) {
            if (!$listing = Listing::where('listing_chain_id', $listingId)->with('highestBid')->first()) {
                return;
            }

            $price = bcmul(
                $listing?->highestBid?->price ?? $listing?->price,
                1.05
            );
            if (bccomp((string) $value, $price) < 0) {
                $fail('enjin-platform::validation.minimum_price')->translate(['price' => $price]);
            }
        }
    }
}
