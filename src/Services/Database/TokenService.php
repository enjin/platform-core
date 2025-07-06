<?php

namespace Enjin\Platform\Services\Database;

use Enjin\Platform\Models\Indexer\Token;
use Enjin\Platform\Models\Indexer\TokenAccount;
use Enjin\Platform\Support\Address;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Database\Eloquent\Builder;

class TokenService
{
    /**
     * Create a new instance.
     */
    public function __construct(protected WalletService $walletService) {}

    /**
     * Get the token balance for an account.
     */
    public function tokenBalanceForAccount(string $collectionId, string $tokenId, ?string $address = null): string
    {
        $publicKey = !empty($address) ? SS58Address::getPublicKey($address) : Address::daemonPublicKey();

        if ($tokenAccount = TokenAccount::find("{$publicKey}-{$collectionId}-{$tokenId}")) {
            return (string) $tokenAccount->balance;
        }

        return '0';
    }

    /**
     * Check if a token exists in a collection.
     */
    public function tokenExistsInCollection(string $tokenId, $collectionId)
    {
        return $this->inCollection($collectionId)
            ->where('token_chain_id', $tokenId)
            ->exists();
    }

    /**
     * Get the generic collection query builder.
     */
    protected function inCollection($collectionId): Builder
    {
        return Token::withoutGlobalScopes()->with('collection')->whereRelation('collection', 'collection_chain_id', $collectionId);
    }
}
