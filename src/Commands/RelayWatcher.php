<?php

namespace Enjin\Platform\Commands;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Clients\Implementations\SubstrateWebsocket;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Models\Wallet;
use Enjin\Platform\Services\Blockchain\Implementations\Substrate;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\DecoderService;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\JSON;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Str;
use Symfony\Component\Console\Command\Command as CommandAlias;

class RelayWatcher extends Command
{
    public $signature = 'platform:relay-watcher';

    public $description;

    protected Codec $codec;

    protected Substrate $rpc;

    public function __construct()
    {
        parent::__construct();
        $this->description = 'Watches managed wallet at relay chain to auto teleport their ENJ';
        $this->codec = new Codec();
        $this->rpc = new Substrate(new SubstrateWebsocket('wss://rpc.relay.canary.enjin.io'));
    }

    public function handle(): int
    {
        $this->warn('Subscribing to any changes on account storage');
        $this->rpc->getClient()->setTimeout(50000);

        $decoder = new DecoderService(network: 'canary-relaychain');

        try {
            $this->rpc->callMethod('state_subscribeStorage', [['0x26aa394eea5630e07c48ae0c9558cef780d41e5e16056765bc8461851072c9d7']]);
            while (true) {
                if ($response = $this->rpc->getClient()->receive()) {
                    $result = Arr::get(JSON::decode($response, true), 'params.result');
                    $block = Arr::get($result, 'changes.0.0'); // block
                    $events = Arr::get($result, 'changes.0.1'); // events
                    $decodedEvents = $decoder->decode('events', $events);
                    $this->findEndowedAccounts($decodedEvents);
                }
            }
        } finally {
            $this->rpc->getClient()->close();
        }

        return CommandAlias::SUCCESS;
    }

    protected function findEndowedAccounts(array $events): void
    {
        array_filter(
            $events,
            function ($event) {
                if ($event->module === 'Balances' && $event->name === 'Transfer') {
                    $this->info('Transfer event found: ' . json_encode($event));

                    if (in_array($account = HexConverter::prefix($event->to), Account::managedPublicKeys())) {
                        $this->info(json_encode($event));
                        $this->createDaemonTransaction($account, $event->amount);
                    }
                }
            }
        );
    }

    protected function createDaemonTransaction(string $account, string $amount): void
    {
        $this->info('Lets create a transaction to teleport the ENJ from: ' . $account);

        $managedWallet = Wallet::firstWhere([
            'public_key' => $account,
        ]);

        $amount = $this->codec->encoder()->compact($amount);
        $call = '0x630903000100a10f0300010100' . HexConverter::unPrefix($managedWallet->public_key);
        $call .= '030400000000' . HexConverter::unPrefix($amount);
        $call .= '0000000000';

        $this->info($call);


        Transaction::create([
            'wallet_public_key' => $managedWallet->public_key,
            'method' => 'LimitedTeleportAssets',
            'state' => TransactionState::PENDING->name,
            'network' => currentRelay()->name,
            'encoded_data' => $call,
            'idempotency_key' => Str::uuid(),
        ]);


        // 63 09 = callIndex = (u8; u8)
        // 03 = dest = XcmVersionedMultiLocation (XcmV3MultiLocation)
        // 00 = parents = u8
        // 01 00 a10f = interior = XcmV3Juntions (XcmV3Junction X1) Parachain Compactu32
        // 03 = beneficiary = XcmVersionedMultiLocation (XcmV3MultiLocation)
        // 00 = parents = u8
        // 01 01 = interior = XcmV3Juntions (XcmV3Junction X1) AccountId32
        // 00 = network = Network = Option<XcmV3JunctionNetworkId>
        // c660fef4c0926e382839d20caee6d4e3adb4d27ec66b223ed6456845196e3e79 = id = [u8;32]
        // 03 04 = assets = Vec<XcmV3MultiassetMultiAssets> (V3)
        // 00 = id = XcmV3MultiassetAssetId (Concrete)
        // 00 = parents = u8
        // 00 = interior = XcmV3Junctions (Here)
        // 00 130000f44482916345 = fun = XcmV3MultiassetFungibility (Fungible) + Amount Compact<u128>
        // 00000000 = feeAssetItem = u32
        // 00 = weightLimit = XcmV3WeightLimit (unlimited)

    }
}
