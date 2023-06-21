<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\Traits;

use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Laravel\Attribute;
use Enjin\Platform\Models\Laravel\Collection;
use Enjin\Platform\Models\Laravel\CollectionAccount;
use Enjin\Platform\Models\Laravel\Token;
use Enjin\Platform\Models\Laravel\TokenAccount;
use Enjin\Platform\Models\Laravel\Wallet;
use Enjin\Platform\Support\SS58Address;
use Log;

trait QueryDataOrFail
{
    protected function getCollection(string $collectionChainId): Collection
    {
        if (!$collection = Collection::where('collection_chain_id', $collectionChainId)->first()) {
            throw new PlatformException(__('enjin-platform::traits.query_data_or_fail.unable_to_find_collection', ['class' => __CLASS__, 'collectionChainId' => $collectionChainId]));
        }

        return $collection;
    }

    protected function getToken(int $collectionId, string $tokenChainId): Token
    {
        if (!$token = Token::where(['collection_id' => $collectionId, 'token_chain_id' => $tokenChainId])->first()) {
            throw new PlatformException(__('enjin-platform::traits.query_data_or_fail.unable_to_find_token', ['class' => __CLASS__, 'tokenChainId' => $tokenChainId, 'collectionId' => $collectionId]));
        }

        return $token;
    }

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

    protected function getWallet(string $publicKey): Wallet
    {
        if (!$wallet = Wallet::where(['public_key' => SS58Address::getPublicKey($publicKey)])->first()) {
            throw new PlatformException(__('enjin-platform::traits.query_data_or_fail.unable_to_find_wallet_account', ['class' => __CLASS__, 'publicKey' => $publicKey]));
        }

        return $wallet;
    }
}
