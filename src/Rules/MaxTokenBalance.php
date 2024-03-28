<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\Rules\Traits\HasDataAwareRule;
use Enjin\Platform\Services\Database\TokenService;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class MaxTokenBalance implements DataAwareRule, ValidationRule
{
    use HasDataAwareRule;
    use HasEncodableTokenId;

    /**
     * The token service.
     */
    protected TokenService $tokenService;

    /**
     * Create instance.
     */
    public function __construct()
    {
        $this->tokenService = resolve(TokenService::class);
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Parse tokenId when there's an adapter
        $chunks = explode('.', $attribute);
        array_pop($chunks);
        if (!$tokenId = $this->encodeTokenId(Arr::get($this->data, implode('.', $chunks)))) {
            // bypass when no tokenId
            return;
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

        if (gmp_sub($tokenAccountBalance, gmp_init($value)) < 0) {
            $fail('enjin-platform::validation.max_token_balance')->translate();
        }
    }
}
