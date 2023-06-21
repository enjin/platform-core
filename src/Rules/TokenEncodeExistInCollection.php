<?php

namespace Enjin\Platform\Rules;

use Enjin\Platform\Services\Database\TokenService;
use Enjin\Platform\Services\Token\TokenIdManager;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;

class TokenEncodeExistInCollection implements DataAwareRule, Rule
{
    /**
     * All of the data under validation.
     */
    protected $data = [];


    /**
     * The token service.
     */
    protected TokenService $tokenService;

    /**
     * The token id manager service.
     */
    protected TokenIdManager $tokenIdManager;

    /**
     * Create a new rule instance.
     */
    public function __construct()
    {
        $this->tokenService = app()->make(TokenService::class);
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
        return $this->tokenService->tokenExistsInCollection(
            $this->tokenIdManager->encode(Arr::wrap(['tokenId' => $value])),
            $this->data['collectionId']
        );
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('enjin-platform::validation.token_encode_exist_in_collection');
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
