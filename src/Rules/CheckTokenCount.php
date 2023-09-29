<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Laravel\Collection;
use Illuminate\Contracts\Validation\ValidationRule;

class CheckTokenCount implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(protected int $offset = 1)
    {
    }

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
        if ($collection = Collection::withCount('tokens')
            ->firstWhere('collection_chain_id', '=', $value)
        ) {
            $total = ($collection->tokens_count + $this->offset);
            if (null !== $collection->max_token_count && (0 === $collection->max_token_count || $total > $collection->max_token_count)) {
                $fail('enjin-platform::validation.check_token_count')
                    ->translate([
                        'total' => $total,
                        'maxToken' => $collection->max_token_count,
                    ]);
            }
        }
    }
}
