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
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\CollectionTransferred;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\Freeze;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\Infused;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\Minted;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\Reserved;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\Thawed;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\TokenAccountCreated;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\TokenAccountDestroyed;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\TokenBurned;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\TokenCreated;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\TokenDestroyed;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\TokenMutated;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\Transferred;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\Unapproved;
use Enjin\Platform\Services\Processor\Substrate\Events\Implementations\MultiTokens\Unreserved;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Enjin\Platform\Traits\EnumExtensions;

enum MultiTokensEventType: string
{
    use EnumExtensions;

    case COLLECTION_CREATED = 'CollectionCreated';
    case COLLECTION_DESTROYED = 'CollectionDestroyed';
    case COLLECTION_MUTATED = 'CollectionMutated';
    case COLLECTION_TRANSFERRED = 'CollectionTransferred';
    case COLLECTION_ACCOUNT_CREATED = 'CollectionAccountCreated';
    case COLLECTION_ACCOUNT_DESTROYED = 'CollectionAccountDestroyed';
    case TOKEN_CREATED = 'TokenCreated';
    case TOKEN_DESTROYED = 'TokenDestroyed';
    case TOKEN_ACCOUNT_CREATED = 'TokenAccountCreated';
    case TOKEN_ACCOUNT_DESTROYED = 'TokenAccountDestroyed';
    case MINTED = 'Minted';
    case BURNED = 'Burned';
    case INFUSED = 'Infused';
    case FROZEN = 'Frozen';
    case THAWED = 'Thawed';
    case TRANSFERRED = 'Transferred';
    case ATTRIBUTE_SET = 'AttributeSet';
    case ATTRIBUTE_REMOVED = 'AttributeRemoved';
    case APPROVED = 'Approved';
    case UNAPPROVED = 'Unapproved';
    case TOKEN_MUTATED = 'TokenMutated';
    case RESERVED = 'Reserved';
    case UNRESERVED = 'Unreserved';

    /**
     * Get the processor for the event.
     */
    public function getProcessor($event, $block, $codec): SubstrateEvent
    {
        return match ($this) {
            self::COLLECTION_CREATED => new CollectionCreated($event, $block, $codec),
            self::COLLECTION_DESTROYED => new CollectionDestroyed($event, $block, $codec),
            self::COLLECTION_MUTATED => new CollectionMutated($event, $block, $codec),
            self::COLLECTION_TRANSFERRED => new CollectionTransferred($event, $block, $codec),
            self::COLLECTION_ACCOUNT_CREATED => new CollectionAccountCreated($event, $block, $codec),
            self::COLLECTION_ACCOUNT_DESTROYED => new CollectionAccountDestroyed($event, $block, $codec),
            self::TOKEN_CREATED => new TokenCreated($event, $block, $codec),
            self::TOKEN_DESTROYED => new TokenDestroyed($event, $block, $codec),
            self::TOKEN_ACCOUNT_CREATED => new TokenAccountCreated($event, $block, $codec),
            self::TOKEN_ACCOUNT_DESTROYED => new TokenAccountDestroyed($event, $block, $codec),
            self::MINTED => new Minted($event, $block, $codec),
            self::BURNED => new TokenBurned($event, $block, $codec),
            self::INFUSED => new Infused($event, $block, $codec),
            self::FROZEN => new Freeze($event, $block, $codec),
            self::THAWED => new Thawed($event, $block, $codec),
            self::TRANSFERRED => new Transferred($event, $block, $codec),
            self::ATTRIBUTE_SET => new AttributeSet($event, $block, $codec),
            self::ATTRIBUTE_REMOVED => new AttributeRemoved($event, $block, $codec),
            self::APPROVED => new Approved($event, $block, $codec),
            self::UNAPPROVED => new Unapproved($event, $block, $codec),
            self::TOKEN_MUTATED => new TokenMutated($event, $block, $codec),
            self::RESERVED => new Reserved($event, $block, $codec),
            self::UNRESERVED => new Unreserved($event, $block, $codec),
        };
    }
}
