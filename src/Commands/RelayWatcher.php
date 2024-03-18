<?php

namespace Enjin\Platform\Commands;

use Codec\Base;
use Codec\ScaleBytes;
use Codec\Types\ScaleInstance;
use Enjin\Platform\Clients\Implementations\DecoderClient;
use Enjin\Platform\Clients\Implementations\SubstrateWebsocket;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Processor\Substrate\DecoderService;
use Enjin\Platform\Support\JSON;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Command\Command as CommandAlias;

class RelayWatcher extends Command
{
    public $signature = 'platform:relay-watcher';

    public $description;

    protected string $nodeUrl = 'wss://rpc.relay.canary.enjin.io';

    public function __construct()
    {
        parent::__construct();

        $this->description = 'Watches managed wallet at relay chain to auto teleport their ENJ';
    }

    public function handle(): int
    {
        $this->warn('Subscribing to any changes on account storage');
        $sub = new Substrate(new SubstrateWebsocket(url: $this->nodeUrl));
        $sub->getClient()->setTimeout(50000);

        $decoder = new DecoderService(network: 'canary-relaychain');


        try {
            $sub->callMethod('state_subscribeStorage', [['0x26aa394eea5630e07c48ae0c9558cef780d41e5e16056765bc8461851072c9d7']]);
            while (true) {
                if ($response = $sub->getClient()->receive()) {
                    $result = Arr::get(JSON::decode($response, true), 'params.result');
                    // block
                    $events = Arr::get($result, 'changes.0.1'); // events

                    $decoded = $decoder->decode('events', $events);
                    dd($decoded);

                }
            }
        } finally {
            $sub->getClient()->close();
        }

        return CommandAlias::SUCCESS;
    }

}


