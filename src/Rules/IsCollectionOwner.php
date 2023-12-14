<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Rules\Traits\HasDataAwareRule;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class IsCollectionOwner implements DataAwareRule, ValidationRule
{
    use HasDataAwareRule;

    public static $bypass = false;

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
        if (!$collection = Collection::firstWhere('collection_chain_id', '=', $value)) {
            $fail('validation.exists')->translate();

            return;
        }

        if (!static::$bypass &&
            (!$collection->owner || !SS58Address::isSameAddress(
                $collection->owner->public_key,
                Arr::get($this->data, 'signingAccount') ?: Account::daemonPublicKey()
            ))
        ) {
            $fail('enjin-platform::validation.is_collection_owner')->translate();
        }
    }
}
