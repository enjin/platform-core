<?php

namespace Enjin\Platform\Rules;

use Enjin\Platform\Services\Database\TokenService;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;

class TokenDoesNotExistInCollection implements DataAwareRule, Rule
{
    /**
     * All of the data under validation.
     */
    protected array $data = [];

    /**
     * The token service.
     */
    protected TokenService $tokenService;

    /**
     * Create a new rule instance.
     */
    public function __construct()
    {
        $this->tokenService = app()->make(TokenService::class);
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
        return !$this->tokenService->tokenExistsInCollection($value, $this->data['collectionId']);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('enjin-platform::validation.token_doesnt_exist_in_collection');
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
