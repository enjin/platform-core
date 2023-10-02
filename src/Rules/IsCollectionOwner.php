<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\ValidationRule;

class IsCollectionOwner implements ValidationRule
{
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
        $owner = Collection::firstWhere('collection_chain_id', '=', $value)?->owner->public_key;

        if (!SS58Address::isSameAddress($owner, Account::daemonPublicKey())) {
            $fail('enjin-platform::validation.is_collection_owner')->translate();
        }
    }
}
