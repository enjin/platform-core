<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Services\Database\CollectionService;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\ValidationRule;

class IsCollectionOwnerOrApproved implements ValidationRule
{
    /**
     * The collection service.
     */
    protected CollectionService $collectionService;

    public function __construct()
    {
        $this->collectionService = resolve(CollectionService::class);
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
        $collection = Collection::firstWhere('collection_chain_id', '=', $value);
        $daemonAccount = Account::daemonPublicKey();

        if (!$collection ||
            (!SS58Address::isSameAddress($collection->owner->public_key, $daemonAccount) &&
            !$this->collectionService->approvalExistsInCollection(
                $collection->collection_chain_id,
                $daemonAccount,
                false,
            ))
        ) {
            $fail('enjin-platform::validation.is_collection_owner_or_approved')->translate();
        }
    }
}
