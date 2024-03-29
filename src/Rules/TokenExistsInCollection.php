<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Rules\Traits\HasDataAwareRule;
use Enjin\Platform\Services\Database\TokenService;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class TokenExistsInCollection implements DataAwareRule, ValidationRule
{
    use HasDataAwareRule;

    /**
     * The token service.
     */
    protected TokenService $tokenService;

    /**
     * Create a new rule instance.
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
        if (!$this->tokenService->tokenExistsInCollection($value, $this->data['collectionId'])) {
            $fail('enjin-platform::validation.token_exists_in_collection')->translate();
        }
    }
}
