<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\Enums\Substrate\ListingState;
use Enjin\Platform\Models\Listing;
use Enjin\Platform\Models\Token;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Translation\PotentiallyTranslatedString;
use Override;

class EnoughTokenSupply implements DataAwareRule, ValidationRule
{
    use HasEncodableTokenId;

    /**
     * All the data under validation.
     */
    protected array $data = [];

    /**
     * Set the data under validation.
     */
    #[Override]
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    #[Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $collectionId = Arr::get($this->data, 'makeAssetId.collectionId');
        $tokenId = Arr::get($this->data, 'makeAssetId.tokenId');
        if (!$collectionId || !$tokenId) {
            return;
        }

        $tokenId = $this->encodeTokenId(Arr::get($this->data, 'makeAssetId'));
        $token = Token::whereHas('collection', fn ($query) => $query->where('collection_chain_id', $collectionId))
            ->firstWhere(['token_chain_id' => $tokenId]);
        if (!$token) {
            return;
        }

        $amount = Listing::whereDoesntHave(
            'state',
            fn ($query) => $query->where('state', ListingState::CANCELLED->name)
        )->where([
            'make_collection_chain_id' => $collectionId,
            'make_token_chain_id' => $tokenId,
        ])->sum('amount');

        if ($token->supply < ($value + $amount)) {
            $fail('enjin-platform::validation.enough_token_supply')->translate();
        }
    }
}
