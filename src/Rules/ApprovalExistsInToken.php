<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Indexer\TokenAccount;
use Enjin\Platform\Rules\Traits\HasDataAwareRule;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Translation\PotentiallyTranslatedString;

class ApprovalExistsInToken implements DataAwareRule, ValidationRule
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
        $operatorId = $this->data['operator'];
        $collectionId = $this->data['collectionId'];
        $tokenId = $this->encodeTokenId($this->data);

        $ownerId = Collection::find($collectionId)?->owner->id;
        $tokenAccount = TokenAccount::find("{$ownerId}-{$collectionId}-{$tokenId}");

        $hasOperator = collect($tokenAccount?->approvals ?? [])->filter(
            fn ($a) => SS58Address::isSameAddress(Arr::get($a, 'accountId'), $operatorId)
        );

        if (!$tokenAccount || $hasOperator->count() === 0) {
            $fail('enjin-platform::validation.approval_exists_in_token')
                ->translate([
                    'operator' => $operatorId,
                    'collectionId' => $collectionId,
                    'tokenId' => $tokenId,
                ]);
        }
    }
}
