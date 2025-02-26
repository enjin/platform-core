<?php

namespace Enjin\Platform\Enums\Substrate;

use Enjin\Platform\Exceptions\PlatformException;

class StorageKey
{
    public function __construct(public StorageType $type, public string $value) {}

    public static function collections(?string $value = null): self
    {
        return new self(StorageType::COLLECTIONS, $value ?? StorageType::COLLECTIONS->value);
    }

    public static function pendingCollectionTransfers(?string $value = null): self
    {
        return new self(StorageType::PENDING_COLLECTION_TRANSFERS, $value ?? StorageType::PENDING_COLLECTION_TRANSFERS->value);
    }

    public static function collectionAccounts(?string $value = null): self
    {
        return new self(StorageType::COLLECTION_ACCOUNTS, $value ?? StorageType::COLLECTION_ACCOUNTS->value);
    }

    public static function tokens(?string $value = null): self
    {
        return new self(StorageType::TOKENS, $value ?? StorageType::TOKENS->value);
    }

    public static function tokenAccounts(?string $value = null): self
    {
        return new self(StorageType::TOKEN_ACCOUNTS, $value ?? StorageType::TOKEN_ACCOUNTS->value);
    }

    public static function attributes(?string $value = null): self
    {
        return new self(StorageType::ATTRIBUTES, $value ?? StorageType::ATTRIBUTES->value);
    }

    public static function events(?string $value = null): self
    {
        return new self(StorageType::EVENTS, $value ?? StorageType::EVENTS->value);
    }

    public static function systemAccount(?string $value = null): self
    {
        return new self(StorageType::SYSTEM_ACCOUNT, $value ?? StorageType::SYSTEM_ACCOUNT->value);
    }

    public static function tanks(?string $value = null): self
    {
        return new self(StorageType::TANKS, $value ?? StorageType::TANKS->value);
    }

    public static function accounts(?string $value = null): self
    {
        return new self(StorageType::ACCOUNTS, $value ?? StorageType::ACCOUNTS->value);
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
            StorageType::TANKS => 'tanksStorages',
            StorageType::ACCOUNTS => 'accountsStorages',
            StorageType::LISTINGS => 'listingsStorages',
            default => throw new PlatformException('No parser for this storage key.'),
        };
    }

    public function parserFacade(): string
    {
        return '\Facades\Enjin\Platform\Services\Processor\Substrate\Parser';
    }

    public static function listings(?string $value = null): self
    {
        return new self(StorageType::LISTINGS, $value ?? StorageType::LISTINGS->value);
    }
}
