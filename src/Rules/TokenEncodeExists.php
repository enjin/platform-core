<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Rules\Traits\HasDataAwareRule;
use Enjin\Platform\Services\Token\TokenIdManager;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class TokenEncodeExists implements DataAwareRule, ValidationRule
{
    use HasDataAwareRule;

    /**
     * The token id manager service.
     */
    protected TokenIdManager $tokenIdManager;

    /**
     * Create a new rule instance.
     */
    public function __construct()
    {
        $this->tokenIdManager = resolve(TokenIdManager::class);
    }

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
        if (!Token::whereTokenChainId($this->tokenIdManager->encode($this->data))->exists()) {
            $fail('enjin-platform::validation.token_encode_exists')->translate();
        }
    }
}
