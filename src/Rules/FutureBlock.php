<?php

namespace Enjin\Platform\Rules;

use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Services\Processor\Substrate\BlockProcessor;
use Illuminate\Contracts\Validation\Rule;

class FutureBlock implements Rule
{
    /**
     * The latest block on-chain.
     *
     * @var int
     */
    protected int $latestBlock;

    /**
     * Create a new rule instance.
     */
    public function __construct()
    {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $this->latestBlock = app()->runningUnitTests()
            ? (int) Block::max('number')
            : (int) ((new BlockProcessor())->latestBlock() ?: Block::max('number'));

        return $this->latestBlock < $value;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('enjin-platform::validation.future_block', ['block' => $this->latestBlock]);
    }
}
