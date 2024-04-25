<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events;

use Enjin\Platform\Enums\Global\ModelType;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Attribute;
use Enjin\Platform\Models\Laravel\Block;
use Enjin\Platform\Models\Laravel\Collection;
use Enjin\Platform\Models\Laravel\CollectionAccount;
use Enjin\Platform\Models\Laravel\Token;
use Enjin\Platform\Models\Laravel\TokenAccount;
use Enjin\Platform\Models\Laravel\Transaction;
use Enjin\Platform\Models\Laravel\Wallet;
use Enjin\Platform\Models\Syncable;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Support\SS58Address;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

abstract class SubstrateEvent
{
    abstract public function run(Event $event, Block $block, Codec $codec);

    public function getValue(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (Arr::has($data, $key)) {
                return Arr::get($data, $key);
            }
        }

        return null;
    }

    public function getTransaction(Block $block, int $extrinsicIndex): ?Transaction
    {
        $extrinsic = Arr::get($block->extrinsics, $extrinsicIndex);

        if (!empty($extrinsic)) {
            return Transaction::firstWhere(['transaction_chain_hash' => $extrinsic->hash]);
        }

        return null;
    }

    public function firstOrStoreAccount(?string $account): ?Model
    {
        if (is_null($account)) {
            return null;
        }

        return WalletService::firstOrStore(['account' => $account]);
    }

    /**
     * @throws PlatformException
     */
    protected function getCollection(string $collectionChainId): Collection
    {
        if (!$collection = Collection::where('collection_chain_id', $collectionChainId)->first()) {
            throw new PlatformException(__('enjin-platform::traits.query_data_or_fail.unable_to_find_collection', ['class' => __CLASS__, 'collectionChainId' => $collectionChainId]));
        }

        return $collection;
    }

    /**
     * @throws PlatformException
     */
    protected function getToken(int $collectionId, string $tokenChainId): Token
    {
        if (!$token = Token::where(['collection_id' => $collectionId, 'token_chain_id' => $tokenChainId])->first()) {
            throw new PlatformException(__('enjin-platform::traits.query_data_or_fail.unable_to_find_token', ['class' => __CLASS__, 'tokenChainId' => $tokenChainId, 'collectionId' => $collectionId]));
        }

        return $token;
    }

    /**
     * @throws PlatformException
     */
    protected function getAttribute(int $collectionId, ?int $tokenId, string $key): Attribute
    {
        if (!$attribute = Attribute::where([
            'collection_id' => $collectionId,
            'token_id' => $tokenId,
            'key' => $key,
        ])->first()) {
            throw new PlatformException(__('enjin-platform::traits.query_data_or_fail.unable_to_find_attribute', ['class' => __CLASS__, 'tokenId' => $tokenId, 'collectionId' => $collectionId, 'key' => $key]));
        }

        return $attribute;
    }

    protected function getCollectionAccount(int $collectionId, int $walletId): CollectionAccount
    {
        if (!$collectionAccount = CollectionAccount::where([
            'collection_id' => $collectionId,
            'wallet_id' => $walletId,
        ])->first()) {
            Log::error(__('enjin-platform::traits.query_data_or_fail.unable_to_find_collection_account', ['class' => __CLASS__, 'walletId' => $walletId, 'collectionId' => $collectionId]));

            return CollectionAccount::create([
                'collection_id' => $collectionId,
                'wallet_id' => $walletId,
            ]);

            // We will not throw an exception here until we can make sure this never happens
            // throw new PlatformException(__('enjin-platform::traits.query_data_or_fail.unable_to_find_collection_account', ['class' => __CLASS__, 'walletId' => $walletId, 'collectionId' => $collectionId]));
        }

        return $collectionAccount;
    }

    /**
     * @throws PlatformException
     */
    protected function getTokenAccount(int $collectionId, int $tokenId, int $walletId): TokenAccount
    {
        if (!$tokenAccount = TokenAccount::where([
            'wallet_id' => $walletId,
            'collection_id' => $collectionId,
            'token_id' => $tokenId,
        ])->first()) {
            throw new PlatformException(__('enjin-platform::traits.query_data_or_fail.unable_to_find_token_account', ['class' => __CLASS__, 'walletId' => $walletId, 'collectionId' => $collectionId, 'tokenId' => $tokenId]));
        }

        return $tokenAccount;
    }

    /**
     * @throws PlatformException
     */
    protected function getWallet(string $publicKey): Wallet
    {
        if (!$wallet = Wallet::where(['public_key' => SS58Address::getPublicKey($publicKey)])->first()) {
            throw new PlatformException(__('enjin-platform::traits.query_data_or_fail.unable_to_find_wallet_account', ['class' => __CLASS__, 'publicKey' => $publicKey]));
        }

        return $wallet;
    }

    protected function shouldSyncCollection(?string $collectionId): bool
    {
        if (!$collectionId) {
            return false;
        }

        return $this->shouldSync(ModelType::COLLECTION->value, $collectionId);
    }

    protected function shouldSync(string $model, string $value): bool
    {
        if (config('enjin-platform.sync.all')) {
            return true;
        }

        return Syncable::query()
            ->where('syncable_type', $model)
            ->where('syncable_id', $value)
            ->exists();
    }
}
