<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Indexer\CollectionAccount;
use Enjin\Platform\Rules\Traits\HasDataAwareRule;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Translation\PotentiallyTranslatedString;

class ApprovalExistsInCollection implements DataAwareRule, ValidationRule
{
    use HasDataAwareRule;

    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $operatorId = $this->data['operator'];
        $collectionId = $this->data['collectionId'];

        $ownerId = Collection::find($collectionId)?->owner->id;
        $collectionAccount = CollectionAccount::find("{$ownerId}-{$collectionId}");

        $hasOperator = collect($collectionAccount?->approvals ?? [])->filter(
            fn ($a) => SS58Address::isSameAddress(Arr::get($a, 'accountId'), $operatorId)
        );

        if (!$collectionAccount || $hasOperator->count() === 0) {
            $fail('enjin-platform::validation.approval_exists_in_collection')
                ->translate([
                    'operator' => $operatorId,
                    'collectionId' => $collectionId,
                ]);
        }
    }
}
