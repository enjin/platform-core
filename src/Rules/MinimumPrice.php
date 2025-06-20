<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Laravel\MarketplaceListing;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class MinimumPrice implements DataAwareRule, ValidationRule
{
    /**
     * All of the data under validation.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Set the data under validation.
     *
     * @param  array  $data
     * @return $this
     */
    #[\Override]
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    #[\Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($listingId = Arr::get($this->data, 'listingId')) {
            if (!$listing = MarketplaceListing::where('listing_chain_id', $listingId)->with('highestBid')->first()) {
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
