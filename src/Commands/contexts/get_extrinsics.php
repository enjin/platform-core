<?php

use Amp\Sync\Channel;
use Enjin\Platform\Clients\Implementations\AsyncWebsocket;
use Illuminate\Support\Arr;

return function (Channel $channel): array {
    $receivedMessage = $channel->receive();
    $nodeUrl = $receivedMessage[0];
    $interval = $receivedMessage[1];
    $current = $receivedMessage[2];
    $to = $receivedMessage[3];

    $rpc = new AsyncWebsocket($nodeUrl);
    $data = [];

    while (true) {
        $blockHash = $rpc->send('chain_getBlockHash', [$current]);
        $extrinsics = Arr::get($rpc->send('chain_getBlock', [$blockHash]), 'block.extrinsics', []);
        $data[$current] = $extrinsics;

        $current += $interval;
        if ($current > $to) {
            $rpc->close();

            break;
        }
    }

    return $data;
};
