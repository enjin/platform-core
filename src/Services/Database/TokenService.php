<?php

namespace Enjin\Platform\Services\Database;

use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Attribute;
use Enjin\Platform\Models\Laravel\Collection;
use Enjin\Platform\Models\Laravel\Wallet;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Models\TokenAccountApproval;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TokenService
{
    /**
     * Create a new instance.
     */
    public function __construct(protected WalletService $walletService)
    {
    }

    /**
     * Create a new token.
     */
    public function store(array $data): Model
    {
        return Token::create($data);
    }

    /**
     * Insert a new token.
     */
    public function insert(array $data): bool
    {
        return Token::insert($data);
    }

    /**
     * Update or create a token.
     */
    public function updateOrStore(array $keys, array $data): Model
    {
        return Token::updateOrCreate($keys, $data);
    }

    /**
     * Get the token balance for an account.
     */
    public function tokenBalanceForAccount(string $collectionId, string $tokenId, ?string $address = null): string
    {
        $publicKey = !empty($address) ? SS58Address::getPublicKey($address) : Account::daemon()->public_key;
        if (!($accountWallet = Wallet::withoutGlobalScopes()->firstWhere(['public_key' => $publicKey]))
            || !($collection = Collection::withoutGlobalScopes()->firstWhere(['collection_chain_id' => $collectionId]))
            || !($token = Token::withoutGlobalScopes()->firstWhere(['token_chain_id' => $tokenId, 'collection_id' => $collection->id]))
        ) {
            return '0';
        }

        $tokenAccount = TokenAccount::withoutGlobalScopes()->whereCollectionId($collection->id)
            ->whereTokenId($token->id)
            ->whereWalletId($accountWallet->id)
            ->first();

        return (string) ($tokenAccount?->balance ?: 0);
    }

    /**
     * Check if an account exists in a token.
     */
    public function accountExistsInToken(string $collectionId, string $tokenId, string $account): bool
    {
        $accountWallet = $this->walletService->firstOrStore(['public_key' => SS58Address::getPublicKey($account)]);
        if (!($collection = Collection::withoutGlobalScopes()->firstWhere(['collection_chain_id' => $collectionId]))
            || !($token = Token::withoutGlobalScopes()->firstWhere(['token_chain_id' => $tokenId, 'collection_id' => $collection->id]))
        ) {
            return false;
        }

        return TokenAccount::withoutGlobalScopes()->whereCollectionId($collection->id)
            ->whereTokenId($token->id)
            ->whereWalletId($accountWallet->id)
            ->exists();
    }

    /**
     * Check if an attribute key exists in a token.
     */
    public function attributeExistsInToken(string $collectionId, string $tokenId, string $key): bool
    {
        if (!($collection = Collection::withoutGlobalScopes()->firstWhere(['collection_chain_id' => $collectionId]))
            || !($token = Token::withoutGlobalScopes()->firstWhere(['token_chain_id' => $tokenId, 'collection_id' => $collection->id]))
        ) {
            return false;
        }

        return Attribute::withoutGlobalScopes()->whereCollectionId($collection->id)
            ->whereTokenId($token->id)
            ->where('key', '=', $key)
            ->exists();
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
     * Check if an operator has approval for an account in a token.
     */
    public function approvalExistsInToken(string $collectionId, string $tokenId, string $operator): bool
    {
        $operatorWallet = $this->walletService->firstOrStore(['public_key' => SS58Address::getPublicKey($operator)]);
        if (!($collection = Collection::withoutGlobalScopes()->firstWhere(['collection_chain_id' => $collectionId]))
            || !($token = Token::withoutGlobalScopes()->firstWhere(['token_chain_id' => $tokenId, 'collection_id' => $collection->id]))
        ) {
            return false;
        }

        $tokenAccount = TokenAccount::withoutGlobalScopes()->whereCollectionId($collection->id)
            ->whereTokenId($token->id)
            ->where('wallet_id', '=', Account::daemon()->id)
            ->first();

        if (!$tokenAccount) {
            return false;
        }

        return TokenAccountApproval::withoutGlobalScopes()->where('token_account_id', $tokenAccount->id)
            ->where('wallet_id', $operatorWallet->id)
            ->exists();
    }

    /**
     * Get a token from a collection.
     */
    public function getTokenFromCollection(string $tokenId, $collectionId): Model
    {
        $token = $this->inCollection($collectionId)
            ->where('token_chain_id', $tokenId)
            ->first();

        if (!$token) {
            throw new PlatformException(__('enjin-platform::error.token_not_found'), 404);
        }

        return $token;
    }

    public function getTokensFromCollection($collectionId, ?array $tokenIds = null, $paginationLimit = null): array
    {
        return $this->inCollection($collectionId)
            ->when($tokenIds ?? false, function (Builder $query) use ($tokenIds) {
                $query->whereIn('token_chain_id', $tokenIds);
            })->cursorPaginateWithTotalDesc('id', $paginationLimit ?? config('enjin-platform.pagination.limit'));
    }

    /**
     * Get the generic collection query builder.
     */
    protected function inCollection($collectionId): Builder
    {
        return Token::withoutGlobalScopes()->with('collection')->whereRelation('collection', 'collection_chain_id', $collectionId);
    }
}
