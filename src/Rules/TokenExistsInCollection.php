<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\Rules\Traits\HasDataAwareRule;
use Enjin\Platform\Services\Database\TokenService;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class TokenExistsInCollection implements DataAwareRule, ValidationRule
{
    use HasDataAwareRule;
    use HasEncodableTokenId;

    /**
     * The token service.
     */
    protected TokenService $tokenService;

    /**
     * Create a new rule instance.
     */
    public function __construct(protected ?string $collectionId = null)
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
        $collectionId = $this->collectionId ?? $this->data['collectionId'] ?? null;

        if (!$collectionId) {
            return;
        }

        $tokenId = is_array($value) ? $this->encodeTokenId($value) : $value;

        if (!$this->tokenService->tokenExistsInCollection($tokenId, $collectionId)) {
            $fail('enjin-platform::validation.token_exists_in_collection')->translate();
        }
    }
}
