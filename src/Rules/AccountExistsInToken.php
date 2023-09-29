<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\Rules\Traits\HasDataAwareRule;
use Enjin\Platform\Services\Database\TokenService;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class AccountExistsInToken implements DataAwareRule, ValidationRule
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
     * @param string $attribute
     * @param mixed  $value
     * @param Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     *
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $tokenId = $this->encodeTokenId($this->data);

        if (!$tokenId || !$this->tokenService->accountExistsInToken($this->data['collectionId'], $tokenId, $value)) {
            $fail('enjin-platform::validation.account_exists_in_token')
                ->translate([
                    'account' => $this->data['tokenAccount'],
                    'collectionId' => $this->data['collectionId'],
                    'tokenId' => $tokenId,
                ]);
        }
    }
}
