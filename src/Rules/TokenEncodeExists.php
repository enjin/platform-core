<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Rules\Traits\HasDataAwareRule;
use Enjin\Platform\Services\Token\TokenIdManager;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class TokenEncodeExists implements DataAwareRule, ValidationRule
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
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $collectionId = Arr::get($this->data, 'collectionId', 0);
        if (!Token::whereTokenChainId($this->tokenIdManager->encode($this->data))
            ->when(
                $collectionId,
                fn ($query) => $query->whereHas('collection', fn ($subQuery) => $subQuery->where('collection_chain_id', $collectionId))
            )->exists()
        ) {
            $fail('enjin-platform::validation.token_encode_exists')->translate();
        }
    }
}
