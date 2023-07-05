<?php

namespace Enjin\Platform\Rules;

use Enjin\Platform\Models\Collection;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\Rule;

class IsCollectionOwner implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $owner = Collection::firstWhere('collection_chain_id', '=', $value)?->owner->public_key;

        return SS58Address::isSameAddress($owner, Account::daemonPublicKey());
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('enjin-platform::validation.is_collection_owner');
    }
}
