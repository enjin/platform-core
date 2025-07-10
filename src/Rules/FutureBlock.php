<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Indexer\Block;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class FutureBlock implements ValidationRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $blockNumber = Block::max('block_number') ?? 0;

        if ($blockNumber >= $value) {
            $fail('enjin-platform::validation.future_block')
                ->translate([
                    'block' => $blockNumber,
                ]);
        }
    }
}
