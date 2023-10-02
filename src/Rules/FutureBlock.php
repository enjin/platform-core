<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Services\Processor\Substrate\BlockProcessor;
use Illuminate\Contracts\Validation\ValidationRule;

class FutureBlock implements ValidationRule
{
    /**
     * The latest block on-chain.
     *
     * @var int
     */
    protected int $latestBlock;

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
        $this->latestBlock = app()->runningUnitTests()
            ? (int) Block::max('number')
            : (int) ((new BlockProcessor())->latestBlock() ?: Block::max('number'));

        if ($this->latestBlock >= $value) {
            $fail('enjin-platform::validation.future_block')
                ->translate([
                    'block' => $this->latestBlock,
                ]);
        }
    }
}
