<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Rules\Traits\HasDataAwareRule;
use Enjin\Platform\Support\Address;
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
        if (!$collection = Collection::find($value)) {
            $fail('validation.exists')->translate();

            return;
        }

        if (!static::$bypass &&
            (!$collection->owner || !Address::isAccountOwner(
                $collection->owner->id,
                Arr::get($this->data, 'signingAccount') ?: Address::daemonPublicKey()
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
     * Removes the bypass of the validation rule.
     */
    public static function unBypass(): void
    {
        static::$bypass = false;
    }
}
