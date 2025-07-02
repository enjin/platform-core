<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Indexer\CollectionAccount;
use Enjin\Platform\Rules\Traits\HasDataAwareRule;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class AccountExistsInCollection implements DataAwareRule, ValidationRule
{
    use HasDataAwareRule;

    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $collectionId = $this->data['collectionId'];
        $accountId = SS58Address::getPublicKey($value);

        if (CollectionAccount::where('id', "{$accountId}-{$collectionId}")->doesntExist()) {
            $fail('enjin-platform::validation.account_exists_in_collection')
                ->translate([
                    'account' => $value,
                    'collectionId' => $collectionId,
                ]);
        }
    }
}
