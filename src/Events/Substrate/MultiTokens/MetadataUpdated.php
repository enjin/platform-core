<?php

namespace Enjin\Platform\Events\Substrate\MultiTokens;

use Enjin\Platform\Channels\PlatformAppChannel;
use Enjin\Platform\Events\PlatformBroadcastEvent;
use Illuminate\Broadcasting\Channel;

class MetadataUpdated extends PlatformBroadcastEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(string $collectionId, ?string $tokenId = null)
    {
        parent::__construct();

        $this->broadcastData = [
            'collectionId' => $collectionId,
            'tokenId'      => $tokenId,
        ];

        $this->broadcastChannels = [
            new Channel("collection;{$collectionId}"),
            new Channel("token;{$collectionId}-{$tokenId}"),
            new PlatformAppChannel(),
        ];
    }
}
