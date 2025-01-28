<?php

namespace Enjin\Platform\Services\Processor\Substrate;

use Enjin\Platform\Clients\Implementations\SubstrateSocketClient;
use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\CollectionAccount;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Support\JSON;
use Enjin\Platform\Support\SS58Address;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Facades\Enjin\Platform\Services\Processor\Substrate\Parser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class State
{
    protected SubstrateSocketClient $client;

    public function __construct(protected Processor $processor)
    {
        $this->client = new SubstrateSocketClient();
    }

    public function __destruct()
    {
        $this->client->close();
    }

    public function extrinsicsForBlock(array $block): mixed
    {
        if ($block['extrinsics'] === null) {
            return null;
        }

        if ($extrinsics = Cache::get($cacheKey = PlatformCache::BLOCK_EXTRINSICS->key($block['number']))) {
            return $extrinsics;
        }

        $extrinsics = $this->processor->withMetadata(
            'Extrinsics',
            JSON::decode($block['extrinsics']),
            $block['number'],
        );

        if (!$extrinsics) {
            return null;
        }

        return Cache::remember(
            $cacheKey,
            now()->addMinute(),
            fn () => $extrinsics
        );
    }

    public function eventsForBlock(array $block): mixed
    {
        if ($block['events'] === null) {
            return null;
        }

        if ($events = Cache::get($cacheKey = PlatformCache::BLOCK_EVENTS->key($block['number']))) {
            return $events;
        }

        $events = $this->processor->withMetadata(
            'Vec<EventRecord>',
            $block['events'],
            $block['number'],
        );

        if (!$events) {
            return null;
        }

        return Cache::remember(
            $cacheKey,
            now()->addMinute(),
            fn () => $events
        );
    }

    public function getStorage(string $key, ?string $at): mixed
    {
        $data = $this->client->send('state_getStorage', [
            $key,
            $at,
        ]);

        return $data ?: null;
    }

    /**
     * @throws PlatformException
     */
    public function checkCollectionAccount($collection, string $addressId, string $blockHash, ?Codec $codec = null): mixed
    {
        $codec ??= new Codec();
        $collectionId = $collection->collection_chain_id;
        $collectionAccount = $this->getParsedStorage(
            key: $codec->encoder()->collectionAccountStorageKey($collectionId, $addressId),
            at: $blockHash,
            parser: 'collectionAccountStorage',
        );

        if ($collectionAccount === null) {
            $wallet = WalletService::firstOrStore(['account' => $address = $addressId]);

            $collectionAccount = CollectionAccount::where([
                'wallet_id' => $wallet->id,
                'collection_id' => $collection->id,
            ])->first();
            if ($collectionAccount) {
                $collectionAccount->delete();
                Log::info(
                    sprintf(
                        'CollectionAccount of Collection #%s (id %s) and account %s was deleted.',
                        $collectionId,
                        $collection->id,
                        $address,
                    )
                );
            }

            return $collectionAccount;
        }

        Log::info(
            sprintf(
                'CollectionAccount (id %s) of Collection #%s (id %s) and account %s was updated.',
                $collectionAccount->id,
                $collectionId,
                $collection->id,
                SS58Address::encode($addressId),
            )
        );

        return $collectionAccount;
    }

    public function getParsedStorage(string $key, string $at, string $parser): mixed
    {
        $data = $this->getStorage($key, $at);
        if ($data === null) {
            return null;
        }

        try {
            return Parser::{$parser}($key, $data);
        } catch (Throwable) {
            return null;
        }
    }

    public function checkTokenAccount($collection, $token, string $tokenId, string $addressId, string $blockHash, ?Codec $codec = null): void
    {
        $codec ??= new Codec();
        $collectionId = $collection->collection_chain_id;

        $tokenAccount = $this->getParsedStorage(
            key: $codec->encoder()->tokenAccountStorageKey($addressId, $collectionId, $tokenId),
            at: $blockHash,
            parser: 'tokenAccountStorage',
        );

        if ($tokenAccount === null) {
            $wallet = WalletService::firstOrStore(['account' => $address = $addressId]);

            if ($token !== null) {
                TokenAccount::where([
                    'wallet_id' => $wallet->id,
                    'collection_id' => $collection->id,
                    'token_id' => $token->id,
                ])->delete();

                Log::info(
                    sprintf(
                        'TokenAccount of Collection #%s (id %s), Token #%s (id %s) and account %s was deleted.',
                        $collectionId,
                        $collection->id,
                        $tokenId,
                        $token->id,
                        $address,
                    )
                );
            } else {
                Log::info(
                    sprintf(
                        'TokenAccount of Collection #%s (id %s), Token #%s and account %s was deleted.',
                        $collectionId,
                        $collection->id,
                        $tokenId,
                        $addressId
                    )
                );
            }
        } else {
            Log::info(
                sprintf(
                    'TokenAccount (id %s) of Collection #%s (id %s), Token #%s (id %s) and account %s was updated.',
                    $tokenAccount->id,
                    $collectionId,
                    $collection->id,
                    $token->token_chain_id,
                    $token->id,
                    $addressId,
                )
            );
        }
    }

    public function checkToken($collection, string $tokenId, string $blockHash, ?Codec $codec = null): mixed
    {
        $codec ??= new Codec();
        $collectionId = $collection->collection_chain_id;

        $token = $this->getParsedStorage(
            key: $codec->encoder()->tokenStorageKey($collectionId, $tokenId),
            at: $blockHash,
            parser: 'tokenStorage',
        );

        if (!isset($token)) {
            return null;
        }

        Log::info("Token #{$token->token_chain_id} (id {$token->id}) of Collection #{$collectionId} (id {$collection->id}) was updated.");

        return $token;
    }
}
