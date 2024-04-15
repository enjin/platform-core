<?php

use Amp\Future;
use Amp\Sync\Channel;
use Enjin\Platform\Clients\Implementations\AsyncWebsocket;
use Illuminate\Support\Arr;

use function Amp\async;

return function (Channel $channel): array {
    $receivedMessage = $channel->receive();
    $storageKey = $receivedMessage[0];
    $blockHash = $receivedMessage[1];
    $nodeUrl = $receivedMessage[2];

    $rpcKey = new AsyncWebsocket($nodeUrl);
    $rpcStorage = new AsyncWebsocket($nodeUrl);

    $total = 0;
    $storageValues = [];
    $asyncQueries = [];

    while (true) {
        try {
            $keys = $rpcKey->send(
                'state_getKeysPaged',
                [
                    $storageKey->value,
                    1000,
                    $startKey ?? null,
                    $blockHash,
                ]
            );
        } catch (Throwable) {
            continue;
        }

        if (empty($keys)) {
            break;
        }

        $total += count($keys);
        $asyncQueries[] = async(function () use ($rpcStorage, $keys, $blockHash, &$storageValues) {
            $storage = $rpcStorage->send(
                'state_queryStorageAt',
                [
                    $keys,
                    $blockHash,
                ]
            );
            $storageValues[] = Arr::get($storage, '0.changes');
        });

        $startKey = Arr::last($keys);
    }

    Future\await($asyncQueries);

    $rpcStorage->close();
    $rpcKey->close();

    return [$storageValues, $total];
};
