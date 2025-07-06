<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Models\Indexer\Attribute;
use Enjin\Platform\Rules\Traits\HasDataAwareRule;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class AttributeExistsInCollection implements DataAwareRule, ValidationRule
{
    use HasDataAwareRule;

    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $collectionId = $this->data['collectionId'];
        $key = HexConverter::stringToHexPrefixed($value);

        if (Attribute::where('id', "{$collectionId}-{$key}")->doesntExist()) {
            $fail('enjin-platform::validation.attribute_exists_in_collection')->translate();
        }
    }
}
