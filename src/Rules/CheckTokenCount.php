<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Indexer\Collection;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Translation\PotentiallyTranslatedString;

class CheckTokenCount implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(protected int $offset = 1) {}

    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($collection = Collection::withCount('tokens')
            ->firstWhere('collection_id', '=', $value)
        ) {
            $total = ($collection->tokens_count + $this->offset);
            $mintPolicy = $collection->mint_policy;
            $maxTokenCount = Arr::get($mintPolicy, 'maxTokenCount');

            if ($maxTokenCount !== null && ($maxTokenCount === 0 || $total > $maxTokenCount)) {
                $fail('enjin-platform::validation.check_token_count')
                    ->translate([
                        'total' => $total,
                        'maxToken' => $maxTokenCount,
                    ]);
            }
        }
    }
}
