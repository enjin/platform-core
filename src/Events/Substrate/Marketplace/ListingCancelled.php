<?php

namespace Enjin\Platform\Events\Substrate\Marketplace;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Enjin\Platform\Marketplace\Traits\HasCustomQueue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Marketplace\ListingCancelled as ListingCancelledPolkadart;

class ListingCancelled extends PlatformBroadcastEvent
{
    use HasCustomQueue;

    /**
     * Create a new event instance.
     */
    public function __construct(ListingCancelledPolkadart $event, ?Model $transaction = null, ?array $extra = null)
    {
        parent::__construct();

        $this->broadcastData = $event->toBroadcast([
            'idempotencyKey' => $transaction?->idempotency_key,
        ]);

        $this->broadcastChannels = [
            new Channel("listing;{$event->listingId}"),
            new Channel("collection;{$extra['collection_id']}"),
            new Channel("token;{$extra['collection_id']}-{$extra['token_id']}"),
            new Channel($extra['seller']),
            new PlatformAppChannel(),
        ];
    }
}
