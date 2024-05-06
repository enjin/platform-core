<?php

namespace Enjin\Platform\Enums\Substrate;

use Enjin\Platform\Exceptions\PlatformException;

class StorageKey
{
    public function __construct(public StorageType $type, public string $value)
    {
    }

    public static function collections(?string $value = null): self
    {
        return new self(StorageType::COLLECTIONS, $value ?? '0xfa7484c926e764ee2a64df96876c81459200647b8c99af7b8b52752114831bdb');
    }

    public static function pendingCollectionTransfers(?string $value = null): self
    {
        return new self(StorageType::PENDING_COLLECTION_TRANSFERS, $value ?? '0xfa7484c926e764ee2a64df96876c8145ec71cb5fb8f048d4d001b5efa87fcf5b');
    }

    public static function collectionAccounts(?string $value = null): self
    {
        return new self(StorageType::COLLECTION_ACCOUNTS, $value ?? '0xfa7484c926e764ee2a64df96876c814555aac77eef55f610e609e395282fe9a2');
    }

    public static function tokens(?string $value = null): self
    {
        return new self(StorageType::TOKENS, $value ?? '0xfa7484c926e764ee2a64df96876c814599971b5749ac43e0235e41b0d3786918');
    }

    public static function tokenAccounts(?string $value = null): self
    {
        return new self(StorageType::TOKEN_ACCOUNTS, $value ?? '0xfa7484c926e764ee2a64df96876c8145091ba7dd8dcd80d727d06b71fe08a103');
    }

    public static function attributes(?string $value = null): self
    {
        return new self(StorageType::ATTRIBUTES, $value ?? '0xfa7484c926e764ee2a64df96876c8145761e97790c81676703ce25cc0ffeb377');
    }

    public static function events(?string $value = null): self
    {
        return new self(StorageType::EVENTS, $value ?? '0x26aa394eea5630e07c48ae0c9558cef780d41e5e16056765bc8461851072c9d7');
    }

    public static function systemAccount(?string $value = null): self
    {
        return new self(StorageType::SYSTEM_ACCOUNT, $value ?? '0x26aa394eea5630e07c48ae0c9558cef7b99d880ec681799c0cf30e8886371da9');
    }

    /**
     * Get the parser for this storage key.
     */
    public function parser(): string
    {
        return match ($this->type) {
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
