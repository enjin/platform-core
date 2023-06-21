<?php

namespace Enjin\Platform\Enums\Substrate;

use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\Approved;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\AttributeRemoved;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\AttributeSet;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\CollectionAccountCreated;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\CollectionAccountDestroyed;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\CollectionCreated;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\CollectionDestroyed;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\CollectionMutated;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\Freeze;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\Minted;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\Thawed;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\TokenAccountCreated;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\TokenAccountDestroyed;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\TokenBurned;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\TokenCreated;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\TokenDestroyed;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\TokenMutated;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\Transferred;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\Unapproved;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Enjin\Platform\Traits\EnumExtensions;

enum MultiTokensEventType: string
{
    use EnumExtensions;

    case COLLECTION_CREATED = 'CollectionCreated';
    case COLLECTION_DESTROYED = 'CollectionDestroyed';
    case COLLECTION_ACCOUNT_CREATED = 'CollectionAccountCreated';
    case COLLECTION_ACCOUNT_DESTROYED = 'CollectionAccountDestroyed';
    case TOKEN_CREATED = 'TokenCreated';
    case TOKEN_DESTROYED = 'TokenDestroyed';
    case TOKEN_ACCOUNT_CREATED = 'TokenAccountCreated';
    case TOKEN_ACCOUNT_DESTROYED = 'TokenAccountDestroyed';
    case MINTED = 'Minted';
    case BURNED = 'Burned';
    case FROZEN = 'Frozen';
    case THAWED = 'Thawed';
    case TRANSFERRED = 'Transferred';
    case ATTRIBUTE_SET = 'AttributeSet';
    case ATTRIBUTE_REMOVED = 'AttributeRemoved';
    case APPROVED = 'Approved';
    case UNAPPROVED = 'Unapproved';
    case TOKEN_MUTATED = 'TokenMutated';
    case COLLECTION_MUTATED = 'CollectionMutated';

    /**
     * Get the processor for the event.
     */
    public function getProcessor(): SubstrateEvent
    {
        return match ($this) {
            self::COLLECTION_CREATED => new CollectionCreated(),
            self::COLLECTION_DESTROYED => new CollectionDestroyed(),
            self::COLLECTION_ACCOUNT_CREATED => new CollectionAccountCreated(),
            self::COLLECTION_ACCOUNT_DESTROYED => new CollectionAccountDestroyed(),
            self::TOKEN_CREATED => new TokenCreated(),
            self::TOKEN_DESTROYED => new TokenDestroyed(),
            self::TOKEN_ACCOUNT_CREATED => new TokenAccountCreated(),
            self::TOKEN_ACCOUNT_DESTROYED => new TokenAccountDestroyed(),
            self::MINTED => new Minted(),
            self::BURNED => new TokenBurned(),
            self::FROZEN => new Freeze(),
            self::THAWED => new Thawed(),
            self::TRANSFERRED => new Transferred(),
            self::ATTRIBUTE_SET => new AttributeSet(),
            self::ATTRIBUTE_REMOVED => new AttributeRemoved(),
            self::APPROVED => new Approved(),
            self::UNAPPROVED => new Unapproved(),
            self::TOKEN_MUTATED => new TokenMutated(),
            self::COLLECTION_MUTATED => new CollectionMutated(),
        };
    }
}
