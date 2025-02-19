<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\Services\Database\TokenService;
use Illuminate\Contracts\Validation\ValidationRule;

class TokenExistsInCollection implements ValidationRule
{
    use HasEncodableTokenId;

    /**
     * Create a new rule instance.
     */
    public function __construct(protected ?string $collectionId) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->collectionId) {
            return;
        }

        if (!resolve(TokenService::class)->tokenExistsInCollection($this->encodeTokenId($value), $this->collectionId)) {
            $fail('enjin-platform::validation.token_exists_in_collection')->translate();
        }
    }
}
