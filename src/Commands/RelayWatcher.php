<?php

namespace Enjin\Platform\Commands;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Clients\Implementations\SubstrateWebsocket;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Enums\Substrate\SystemEventType;
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
    protected DecoderService $decoder;

    public function __construct()
    {
        parent::__construct();
        $this->description = 'Watches managed wallet at relay chain to auto teleport their ENJ';
        $this->codec = new Codec();
        $this->rpc = new Substrate(new SubstrateWebsocket(networkConfig('node', currentRelay())));
        $this->decoder = new DecoderService(network: currentRelay()->value);
    }

    public function handle(): int
    {
        $sub = new Substrate(new SubstrateWebsocket('wss://rpc.relay.canary.enjin.io'));
        $this->warn('Starting subscription to new heads');

        try {
            $sub->callMethod('chain_subscribeNewHeads');
            $sub->callMethod('state_subscribeStorage', [['0x26aa394eea5630e07c48ae0c9558cef780d41e5e16056765bc8461851072c9d7']]);
            while (true) {
                if ($response = $sub->getClient()->receive()) {
                    $data = JSON::decode($response, true);
                    ray($data);

                    $method = Arr::get($data, 'method');

                    if ($method === 'chain_newHead') {
                        $this->processNewHead($data);
                    }

                    if ($method === 'state_storage') {
                        $this->getEvents($data);
                    }
                }
            }
        } finally {
            $sub->getClient()->close();
        }


        //        $this->getBlocks();
        //        $this->getEvents();

        return CommandAlias::SUCCESS;
    }

    public function getHashWhenBlockIsFinalized(int $blockNumber): string
    {
        while (true) {
            $blockHash = $this->rpc->callMethod('chain_getBlockHash', [$blockNumber]);
            if ($blockHash) {
                $this->rpc->getClient()->close();

                return $blockHash;
            }
            usleep(100000);
        }
    }

    protected function processNewHead($data)
    {
        $syncTime = now();
        $result = Arr::get($data, 'params.result');
        $heightHexed = Arr::get($result, 'number');

        if ($heightHexed === null) {
            return;
        }

        $blockNumber = HexConverter::hexToUInt($heightHexed);
        $blockHash = $this->getHashWhenBlockIsFinalized($blockNumber);

        $this->info(sprintf('Ingested header for block #%s in %s seconds', $blockNumber, now()->diffInMilliseconds($syncTime) / 1000));

        $this->fetchExtrinsics($blockHash, $blockNumber);
    }

    protected function fetchExtrinsics(string $blockHash, $blockNumber): void
    {
        ray('Fetching block for hash ' . $blockHash);
        $data = $this->rpc->callMethod('chain_getBlock', [$blockHash]);
        //        ray($data);

        if ($extrinsics = Arr::get($data, 'block.extrinsics')) {
            for ($i = 0; $i < count($extrinsics); $i++) {
                $decodedExtrinsic = Arr::first($this->decoder->decode('Extrinsics', [$extrinsics[$i]]));
                ray($decodedExtrinsic);

                $module = $decodedExtrinsic?->module;
                $call = $decodedExtrinsic?->call;

                if ($module === 'XcmPallet' && $call === 'limited_teleport_assets') {
                    $hash = $decodedExtrinsic->hash;
                    $signer = $decodedExtrinsic->signer;

                    $tx = Transaction::firstWhere([
                        'transaction_chain_hash' => $hash,
                        'wallet_public_key' => $signer,
                    ]);

                    $tx->transaction_chain_id = $blockNumber . '-' . $i;
                    $tx->state = TransactionState::FINALIZED->name;
                    $tx->save();

                    ray($tx);

                }

                $this->warn('Module: ' . $module);
                $this->warn('Call: ' . $call);
            }


        }

        //        ray($extrinsics);
    }

    protected function getEvents($data): void
    {
        $this->warn('Subscribing to any changes on account storage');
        $result = Arr::get($data, 'params.result');
        $block = Arr::get($result, 'changes.0.0'); // block
        $events = Arr::get($result, 'changes.0.1'); // events
        $decodedEvents = $this->decoder->decode('events', $events);
        $this->findEndowedAccounts($decodedEvents, $block);
    }

    protected function findEndowedAccounts(array $events, string $block): void
    {
        ray($events);
        array_filter(
            $events,
            function ($event) use ($block) {
                if ($event->module === 'Balances' && $event->name === 'Transfer') {
                    if (in_array($account = HexConverter::prefix($event->to), Account::managedPublicKeys())) {
                        $this->info(json_encode($event));
                        $this->createDaemonTransaction($account, $event->amount);
                    }
                }

                if ($event->module === 'XcmPallet' && $event->name === 'Attempted') {
                    $this->info('The block is: ' . $block);
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
