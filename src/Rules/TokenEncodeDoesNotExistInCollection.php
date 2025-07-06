<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Indexer\Token;
use Enjin\Platform\Rules\Traits\HasDataAwareRule;
use Enjin\Platform\Services\Token\TokenIdManager;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

class TokenEncodeDoesNotExistInCollection implements DataAwareRule, ValidationRule
{
    use HasDataAwareRule;

    /**
     * The token id manager service.
     */
    protected TokenIdManager $tokenIdManager;

    /**
     * Create a new rule instance.
     */
    public function __construct()
    {
        $this->tokenIdManager = resolve(TokenIdManager::class);
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $data = Arr::get($this->data, Str::beforeLast($attribute, '.'));
        $collectionId = $this->data['collectionId'];
        $tokenId = $this->tokenIdManager->encode($data);

        if (Token::where('id', "{$collectionId}-{$tokenId}")->exists()) {
            $fail('enjin-platform::validation.token_encode_doesnt_exist_in_collection')->translate();
        }
    }
}
