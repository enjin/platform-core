<?php

namespace Enjin\Platform\Rules;

use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\Services\Database\TokenService;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;

class AccountExistsInToken implements DataAwareRule, Rule
{
    use HasEncodableTokenId;

    /**
     * All of the data under validation.
     */
    protected array $data = [];

    /**
     * The token service.
     */
    protected TokenService $tokenService;

    /**
     * The error message.
     */
    protected string $message;

    /**
     * Create instance.
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
        $tokenId = $this->encodeTokenId($this->data);
        $this->message = __('enjin-platform::validation.account_exists_in_token', ['account' => $this->data['tokenAccount'], 'collectionId' => $this->data['collectionId'], 'tokenId' => $tokenId]);

        if (!$tokenId) {
            return false;
        }

        return $this->tokenService->accountExistsInToken($this->data['collectionId'], $tokenId, $value);
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
