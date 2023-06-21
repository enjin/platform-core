<?php

namespace Enjin\Platform\Rules;

use Enjin\Platform\Models\Collection;
use Enjin\Platform\Services\Database\CollectionService;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\Rule;

class IsCollectionOwnerOrApproved implements Rule
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
        $collection = Collection::firstWhere('collection_chain_id', '=', $value);

        if (!$collection) {
            return false;
        }

        $daemonAccount = config('enjin-platform.chains.daemon-account');

        if (SS58Address::isSameAddress($collection->owner->public_key, $daemonAccount)) {
            return true;
        }

        return app(CollectionService::class)->approvalExistsInCollection(
            $collection->collection_chain_id,
            $daemonAccount,
            false,
        );
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('enjin-platform::validation.is_collection_owner_or_approved');
    }
}
