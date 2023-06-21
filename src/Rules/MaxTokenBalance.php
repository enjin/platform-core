<?php

namespace Enjin\Platform\Rules;

use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\Services\Database\TokenService;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;

class MaxTokenBalance implements DataAwareRule, Rule
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
        // Parse tokenId when there's an adatper
        $chunks = explode('.', $attribute);
        array_pop($chunks);
        if (!$tokenId = $this->encodeTokenId(Arr::get($this->data, implode('.', $chunks)))) {
            // bypass when no tokenId
            return true;
        }

        $tokenAccountBalance = gmp_init($this->tokenService->tokenBalanceForAccount(
            collectionId: Arr::get($this->data, 'collectionId'),
            tokenId: // This gets the ID when the rule is used in a Simple Mutation.
                $tokenId
                // This gets the ID when the rule is used in a Batch Mutation.
                ?? Arr::get($this->data, str_replace('amount', 'tokenId', $attribute)),
            address: // This gets the address for OperatorTransfer when the rule is used in a Simple Mutation.
                Arr::get($this->data, 'params.source')
                // This gets the address for OperatorTransfer when the rule is used in a Batch Mutation.
                ?? Arr::get($this->data, str_replace('amount', 'source', $attribute))
                // This gets the address when using SimpleTransfer either on Simple or Batch Mutation.
                ?? Arr::get($this->data, 'signingAccount'),
        ));

        return gmp_sub($tokenAccountBalance, gmp_init($value)) >= 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('enjin-platform::validation.max_token_balance');
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
