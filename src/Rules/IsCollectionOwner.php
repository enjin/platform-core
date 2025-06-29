<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Rules\Traits\HasDataAwareRule;
use Enjin\Platform\Support\Account;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Translation\PotentiallyTranslatedString;

class IsCollectionOwner implements DataAwareRule, ValidationRule
{
    use HasDataAwareRule;

    public static bool $bypass = false;

    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$collection = Collection::firstWhere('collection_chain_id', '=', $value)) {
            $fail('validation.exists')->translate();

            return;
        }

        if (!static::$bypass &&
            (!$collection->owner || !Account::isAccountOwner(
                $collection->owner->public_key,
                Arr::get($this->data, 'signingAccount') ?: Account::daemonPublicKey()
            ))
        ) {
            $fail('enjin-platform::validation.is_collection_owner')->translate();
        }
    }

    /**
     * Bypass the validation rule.
     */
    public static function bypass(): void
    {
        static::$bypass = true;
    }

    /**
     * Unbypass the validation rule.
     */
    public static function unBypass(): void
    {
        static::$bypass = false;
    }
}
