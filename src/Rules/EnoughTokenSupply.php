<?php

namespace Enjin\Platform\Rules;

use Closure;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\Enums\ListingState;
use Enjin\Platform\Models\MarketplaceListing;
use Enjin\Platform\Models\Token;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class EnoughTokenSupply implements DataAwareRule, ValidationRule
{
    use HasEncodableTokenId;

    /**
     * All of the data under validation.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Set the data under validation.
     *
     * @param  array  $data
     * @return $this
     */
    #[\Override]
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    #[\Override]
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

        $amount = MarketplaceListing::whereDoesntHave(
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
