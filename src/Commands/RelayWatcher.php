<?php

namespace Enjin\Platform\Commands;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Clients\Implementations\SubstrateWebsocket;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Processor\Substrate\DecoderService;
use Enjin\Platform\Support\Account;
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
                    $block = Arr::get($result, 'changes.0.0'); // block
                    $events = Arr::get($result, 'changes.0.1'); // events
                    $decodedEvents = $decoder->decode('events', $events);
                    $this->findEndowedAccounts($decodedEvents);
                }
            }
        } finally {
            $sub->getClient()->close();
        }

        return CommandAlias::SUCCESS;
    }

    protected function findEndowedAccounts(array $events)
    {
        $transfers = array_filter(
            $events,
            function ($event) {
                if ($event->module === 'Balances' && $event->name === 'Transfer') {
                    if (in_array($account = HexConverter::prefix($event->to), Account::managedPublicKeys())) {
                        $this->createDaemonTransaction($account);
                    }
                }
            }
        );
    }

    protected function createDaemonTransaction(string $account)
    {
        $this->info('Lets create a transaction to teleport the ENJ from: ' . $account);

        $managedWallet = Wallet::firstWhere([
            'public_key' => $account,
        ]);

        Transaction::create([
            'wallet_public_key' => $managedWallet->public_key,
            'method' => 'Teleport',
            'state' => TransactionState::PENDING->name,
            'network' => 'canary-relay',
            'encoded_data' => '0xa1028400c660fef4c0926e382839d20caee6d4e3adb4d27ec66b223ed6456845196e3e7901a613c2d47d5326d608a9eac8c2476dd6040cd2ddc5fd3d5d7428adc9a9ea056cf72e5d027479e83b6e61db39fa951b81d59822852df2bad5823b4038e7c2ec84b5020400630903000100a10f0300010100c660fef4c0926e382839d20caee6d4e3adb4d27ec66b223ed6456845196e3e79030400000000130000f444829163450000000000',
            'idempotency_key' => \Str::uuid(),
        ]);
    }
}
