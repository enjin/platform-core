<?php

namespace Enjin\Platform\Rules;

use Enjin\Platform\Models\Laravel\Collection;
use Illuminate\Contracts\Validation\Rule;

class CheckTokenCount implements Rule
{
    /**
     * The error message.
     */
    protected string $message;

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
     *
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($collection = Collection::withCount('tokens')
            ->firstWhere('collection_chain_id', '=', $value)
        ) {
            $total = ($collection->tokens_count + $this->offset);
            if (null !== $collection->max_token_count && (0 === $collection->max_token_count || $total > $collection->max_token_count)) {
                $this->message = __('enjin-platform::validation.check_token_count', ['total' => $total, 'maxToken' => $collection->max_token_count]);

                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }
}
