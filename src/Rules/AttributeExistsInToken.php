<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\Models\Indexer\Attribute;
use Enjin\Platform\Rules\Traits\HasDataAwareRule;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class AttributeExistsInToken implements DataAwareRule, ValidationRule
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
        $collectionId = $this->data['collectionId'];
        $tokenId = $this->encodeTokenId($this->data);
        $key = HexConverter::stringToHexPrefixed($value);

        if (Attribute::where('id', "{$collectionId}-{$tokenId}-{$key}")->doesntExist()) {
            $fail('enjin-platform::validation.key_doesnt_exit_in_token')->translate();
        }
    }
}
