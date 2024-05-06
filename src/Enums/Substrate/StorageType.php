<?php

namespace Enjin\Platform\Enums\Substrate;

enum StorageType
{
    case COLLECTIONS;
    case PENDING_COLLECTION_TRANSFERS;
    case COLLECTION_ACCOUNTS;
    case TOKENS;
    case TOKEN_ACCOUNTS;
    case ATTRIBUTES;
    case EVENTS;
    case SYSTEM_ACCOUNT;
}
