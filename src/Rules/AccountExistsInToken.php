<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\Models\Indexer\TokenAccount;
use Enjin\Platform\Rules\Traits\HasDataAwareRule;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class AccountExistsInToken implements DataAwareRule, ValidationRule
{
    use HasDataAwareRule;
    use HasEncodableTokenId;

    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $collectionId = $this->data['collectionId'];
        $tokenId = $this->encodeTokenId($this->data);
        $accountId = SS58Address::getPublicKey($value);

        if (TokenAccount::where('id', "{$accountId}-{$collectionId}-{$tokenId}")->doesntExist()) {
            $fail('enjin-platform::validation.account_exists_in_token')
                ->translate([
                    'account' => $value,
                    'collectionId' => $collectionId,
                    'tokenId' => $tokenId,
                ]);
        }
    }
}
