<?php

namespace Enjin\Platform\Enums\Substrate;

use Enjin\Platform\Exceptions\PlatformException;

enum StorageType: string
{
    case COLLECTIONS = '0xfa7484c926e764ee2a64df96876c81459200647b8c99af7b8b52752114831bdb';
    case PENDING_COLLECTION_TRANSFERS = '0xfa7484c926e764ee2a64df96876c8145ec71cb5fb8f048d4d001b5efa87fcf5b';
    case COLLECTION_ACCOUNTS = '0xfa7484c926e764ee2a64df96876c814555aac77eef55f610e609e395282fe9a2';
    case TOKENS = '0xfa7484c926e764ee2a64df96876c814599971b5749ac43e0235e41b0d3786918';
    case TOKEN_ACCOUNTS = '0xfa7484c926e764ee2a64df96876c8145091ba7dd8dcd80d727d06b71fe08a103';
    case ATTRIBUTES = '0xfa7484c926e764ee2a64df96876c8145761e97790c81676703ce25cc0ffeb377';
    case EVENTS = '0x26aa394eea5630e07c48ae0c9558cef780d41e5e16056765bc8461851072c9d7';
    case SYSTEM_ACCOUNT = '0x26aa394eea5630e07c48ae0c9558cef7b99d880ec681799c0cf30e8886371da9';
    case TANKS  = '0xb8ed204d2f9b209f43a0487b80cceca11dff2785cc2c6efead5657dc32a2065e';
    case ACCOUNTS = '0xb8ed204d2f9b209f43a0487b80cceca18ee7418a6531173d60d1f6a82d8f4d51';

    /**
     * Get the parser for this storage key.
     */
    public function parser(): string
    {
        return match ($this) {
            StorageType::COLLECTIONS => 'collectionsStorages',
            StorageType::PENDING_COLLECTION_TRANSFERS => 'pendingCollectionTransfersStorages',
            StorageType::COLLECTION_ACCOUNTS => 'collectionsAccountsStorages',
            StorageType::TOKENS => 'tokensStorages',
            StorageType::TOKEN_ACCOUNTS => 'tokensAccountsStorages',
            StorageType::ATTRIBUTES => 'attributesStorages',
            default => throw new PlatformException('No parser for this storage key.'),
        };
    }

    public function parserFacade(): string
    {
        return '\Facades\Enjin\Platform\Services\Processor\Substrate\Parser';
    }
}
