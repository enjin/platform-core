<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Laravel\Collection;
use Enjin\Platform\Rules\Traits\HasDataAwareRule;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class AccountWaitingCollectionTransfer implements DataAwareRule, ValidationRule
{
    use HasDataAwareRule;

    /**
     * Create a new rule instance.
     */
    public function __construct(protected string $account)
    {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $collection = Collection::firstWhere('collection_chain_id', $this->data['collectionId']);
        $transferTo = $collection?->pending_transfer;

        if ($transferTo === null || !SS58Address::isSameAddress($this->account, $transferTo)) {
            $fail('enjin-platform::validation.account_waiting_collection_transfer')
                ->translate([
                    'account' => $this->account,
                    'collectionId' => $this->data['collectionId'],
                ]);
        }
    }
}
