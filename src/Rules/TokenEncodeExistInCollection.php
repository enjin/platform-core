<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Rules\Traits\HasDataAwareRule;
use Enjin\Platform\Services\Database\TokenService;
use Enjin\Platform\Services\Token\TokenIdManager;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Translation\PotentiallyTranslatedString;

class TokenEncodeExistInCollection implements DataAwareRule, ValidationRule
{
    use HasDataAwareRule;

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
        $this->tokenService = resolve(TokenService::class);
        $this->tokenIdManager = resolve(TokenIdManager::class);
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->tokenService->tokenExistsInCollection(
            $this->tokenIdManager->encode(Arr::wrap(['tokenId' => $value])),
            $this->data['collectionId']
        )) {
            $fail('enjin-platform::validation.token_encode_exist_in_collection')->translate();
        }
    }
}
