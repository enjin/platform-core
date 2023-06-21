<?php

namespace Enjin\Platform\Rules;

use Enjin\Platform\Models\Token;
use Enjin\Platform\Services\Token\TokenIdManager;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;

class TokenEncodeExists implements DataAwareRule, Rule
{
    /**
     * All of the data under validation.
     *
     * @var array
     */
    protected $data = [];

    /**
     * The token id manager service.
     */
    protected TokenIdManager $tokenIdManager;

    /**
     * Create a new rule instance.
     */
    public function __construct()
    {
        $this->tokenIdManager = app()->make(TokenIdManager::class);
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
        return Token::whereTokenChainId($this->tokenIdManager->encode($this->data))->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('enjin-platform::validation.token_encode_exists');
    }

    /**
     * Set the data under validation.
     *
     * @param array $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }
}
