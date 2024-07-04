<?php

namespace Enjin\Platform\Enums\Global;

use Enjin\Platform\Interfaces\PlatformCacheable;
use Enjin\Platform\Traits\EnumExtensions;
use Illuminate\Support\Collection;

enum PlatformCache: string implements PlatformCacheable
{
    use EnumExtensions;

    case METADATA = 'metadata';
    case CALL_INDEXES = 'callIndexes';
    case SYSTEM_ACCOUNT = 'systemAccount';
    case CUSTOM_TYPES = 'customTypes';
    case MANAGED_ACCOUNTS = 'managedAccounts';
    case BALANCE = 'balance';
    case BLOCK_EVENTS = 'blockEvents';
    case BLOCK_EXTRINSICS = 'blockExtrinsics';
    case SYNCING_IN_PROGRESS = 'syncingInProgress';
    case VERIFY_ACCOUNT = 'verifyAccount';
    case PAGINATION = 'pagination';
    case FEE = 'fee';
    case DEPOSIT = 'deposit';
    case RELEASE_DIFF = 'releaseDiff';

    case BLOCK_EVENT_COUNT = 'blockEventCount';

    case BLOCK_TRANSACTION = 'blockTransaction';

    public function key(?string $suffix = null, ?string $network = null): string
    {
        return 'enjin-platform:core:' . currentMatrix()->value . ':' . $this->value . ($suffix ? ":{$suffix}" : '');
    }

    public static function clearable(): Collection
    {
        return collect([
            self::METADATA,
            self::CALL_INDEXES,
            self::CUSTOM_TYPES,
            self::MANAGED_ACCOUNTS,
            self::RELEASE_DIFF,
        ]);
    }
}
