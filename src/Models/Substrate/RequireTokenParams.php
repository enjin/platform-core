<?php

namespace Enjin\Platform\Models\Substrate;

use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Models\TokenAccount;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Arr;

class RequireTokenParams extends FuelTankRules
{
    /**
     * Creates a new instance.
     */
    public function __construct(
        public string $collectionId,
        public string $tokenId,
    ) {}

    /**
     * Creates a new instance from the given array.
     */
    public static function fromEncodable(array $params): self
    {
        $collectionId = Arr::get($params, 'RequireToken.collectionId') ?? Arr::get($params, 'RequireToken.collection_id');
        $tokenId = Arr::get($params, 'RequireToken.tokenId') ?? Arr::get($params, 'RequireToken.token_id');

        return new self(
            collectionId: gmp_strval($collectionId),
            tokenId: gmp_strval($tokenId),
        );
    }

    /**
     * Returns the encodable representation of this instance.
     */
    public function toEncodable(): array
    {
        return ['RequireToken' => [
            'collectionId' => $this->collectionId,
            'tokenId' => $this->tokenId,
        ]];
    }

    public function toArray(): array
    {
        return ['RequireToken' => [
            'collectionId' => $this->collectionId,
            'tokenId' => $this->tokenId,
        ]];
    }

    public function validate(string $caller): bool
    {
        if (!($collection = Collection::firstWhere('collection_chain_id', $this->collectionId))) {
            return false;
        }

        if (!($token = Token::firstWhere([
            'collection_id' => $collection->id,
            'token_chain_id' => $this->tokenId,
        ]))) {
            return false;
        }

        $wallet = WalletService::firstOrStore([
            'public_key' => $caller,
        ]);

        if (!($tokenAccount = TokenAccount::firstWhere([
            'wallet_id' => $wallet->id,
            'token_id' => $token->id,
        ]))) {
            return false;
        }

        return $tokenAccount->balance > 0;
    }
}
