<?php

namespace Enjin\Platform\Services\Database;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Jobs\HotSync;
use Enjin\Platform\Models\Attribute;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\CollectionAccount;
use Enjin\Platform\Models\CollectionAccountApproval;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Database\Eloquent\Model;

class CollectionService
{
    /**
     * Create a new CollectionService instance.
     */
    public function __construct(protected WalletService $walletService)
    {
    }

    /**
     * Get the collection by column and value.
     */
    public function get(string $index, string $column = 'collection_chain_id'): Model
    {
        return Collection::where($column, '=', $index)->firstOrFail();
    }

    /**
     * Create a new collection.
     */
    public function store(array $data): Model
    {
        return Collection::create($data);
    }

    /**
     * Insert a new collection.
     */
    public function insert(array $data): bool
    {
        return Collection::insert($data);
    }

    /**
     * Update ot insert a collection.
     */
    public function updateOrInsert(array $keys, array $data)
    {
        return Collection::updateOrInsert(
            $keys,
            $data
        );
    }

    public function hotSync(string $collectionId): void
    {
        $storageKeys = Substrate::getStorageKeysForCollectionId($collectionId);

        HotSync::dispatch($storageKeys);
    }

    /**
     * Check if the attribute key exists in the collection.
     */
    public function attributeExistsInCollection(string $collectionId, string $key): bool
    {
        return Attribute::withoutGlobalScopes()->with('collection')
            ->whereRelation('collection', 'collection_chain_id', $collectionId)
            ->where('key', '=', HexConverter::stringToHexPrefixed($key))
            ->exists();
    }

    /**
     * Check if the account exists in the collection.
     */
    public function accountExistsInCollection(string $collectionId, string $account): bool
    {
        $accountWallet = $this->walletService->firstOrStore(['public_key' => SS58Address::getPublicKey($account)]);

        return CollectionAccount::withoutGlobalScopes()->with('collection')
            ->whereRelation('collection', 'collection_chain_id', $collectionId)
            ->where('wallet_id', '=', $accountWallet->id)
            ->exists();
    }

    /**
     * Check if the approval exists in the collection.
     */
    public function approvalExistsInCollection(
        string $collectionId,
        string $operator,
        bool $hasAccountForDaemon = true,
    ): bool {
        $operatorWallet = $this->walletService->firstOrStore(['public_key' => SS58Address::getPublicKey($operator)]);

        $collectionAccount = CollectionAccount::withoutGlobalScopes()->with(['collection', 'wallet'])
            ->whereRelation('collection', 'collection_chain_id', $collectionId)
            ->when(
                $hasAccountForDaemon,
                fn ($query) => $query->where('wallet_id', '=', Account::daemon()->id)
            )
            ->get();

        if ($collectionAccount->isEmpty()) {
            return false;
        }

        return CollectionAccountApproval::withoutGlobalScopes()->with(['account', 'wallet'])
            ->whereBelongsTo($collectionAccount, 'account')
            ->whereBelongsTo($operatorWallet, 'wallet')
            ->exists();
    }
}
