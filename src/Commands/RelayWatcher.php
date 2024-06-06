<?php

namespace Enjin\Platform\Commands;

use Cache;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Clients\Implementations\SubstrateWebsocket;
use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Enums\Substrate\StorageType;
use Enjin\Platform\Enums\Substrate\SystemEventType;
use Enjin\Platform\Enums\Substrate\XcmOutcome;
use Enjin\Platform\Events\Global\TransactionCreated;
use Enjin\Platform\Events\Substrate\Balances\Teleport;
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
        $this->rpc = new Substrate(new SubstrateWebsocket(currentRelayUrl()));
        $this->decoder = new DecoderService(network: currentRelay()->value);
    }

    public function handle(): int
    {
        $sub = new Substrate(new SubstrateWebsocket(currentRelayUrl()));

        try {
            $this->warn('Subscribing to new heads');
            $sub->callMethod('chain_subscribeNewHeads');
            $this->warn('Subscribing to any changes on account storage');
            $sub->callMethod('state_subscribeStorage', [[StorageType::EVENTS->value]]);

            while (true) {
                if ($response = $sub->getClient()->receive()) {
                    $data = JSON::decode($response, true);
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

    protected function processNewHead($data): void
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
        $data = $this->rpc->callMethod('chain_getBlock', [$blockHash]);

        if ($extrinsics = Arr::get($data, 'block.extrinsics')) {
            for ($i = 0; $i < count($extrinsics); $i++) {
                $decodedExtrinsic = Arr::first($this->decoder->decode('Extrinsics', [$extrinsics[$i]]));
                $module = $decodedExtrinsic?->module;
                $call = $decodedExtrinsic?->call;

                if ($module === 'XcmPallet' && $call === 'limited_teleport_assets') {
                    $hash = $decodedExtrinsic->hash;
                    $signer = $decodedExtrinsic->signer;
                    $tx = Transaction::firstWhere([
                        'transaction_chain_hash' => $hash,
                        'wallet_public_key' => $signer,
                    ]);

                    if ($tx) {
                        $tx->transaction_chain_id = $blockNumber . '-' . $i;
                        $tx->state = TransactionState::FINALIZED->name;
                        $tx->save();

                        $this->updateExtrinsicResult($blockNumber, $i, $tx->id, null);
                    }
                }
            }
        }
    }

    protected function getEvents($data): void
    {
        $result = Arr::get($data, 'params.result');
        $block = Arr::get($result, 'block');
        $events = Arr::get($result, 'changes.0.1');
        $decodedEvents = $this->decoder->decode('events', $events);
        $this->findEndowedAccounts($decodedEvents, $block);
    }

    protected function findEndowedAccounts(array $events, string $blockHash): void
    {
        $blockNumber = $this->getBlockNumber($blockHash);

        array_filter(
            $events,
            function ($event) use ($blockNumber) {
                if ($event->module === 'Balances' && $event->name === 'Transfer') {
                    if (in_array($account = HexConverter::prefix($event->to), Account::managedPublicKeys())) {
                        $this->info(json_encode($event));
                        $this->createDaemonTransaction($account, $event->amount);
                    }
                }

                if ($event->module === 'XcmPallet' && $event->name === 'Attempted') {
                    $successOrFailed = $event->outcome === XcmOutcome::COMPLETE;
                    $this->updateExtrinsicResult($blockNumber, $event->extrinsicIndex, null, $successOrFailed);
                }

                if ($event->module === 'System' && $event->name === 'ExtrinsicFailed') {
                    $this->updateExtrinsicResult($blockNumber, $event->extrinsicIndex, null, false);
                }
            }
        );
    }

    protected function getBlockNumber($blockHash): int
    {
        while (true) {
            $block = $this->rpc->callMethod('chain_getBlock', [$blockHash]);
            if ($block) {
                return HexConverter::hexToUInt(Arr::get($block, 'block.header.number'));
            }

            usleep(100000);
        }
    }

    protected function updateExtrinsicResult($blockNumber, $extrinsicIndex, $transactionId, $success): void
    {
        $extrinsicIdentifier = Cache::remember(
            PlatformCache::BLOCK_TRANSACTION->key($txId = $blockNumber . '-' . $extrinsicIndex),
            now()->addMinutes(5),
            fn () => $txId . ':' . $success,
        );

        if ($transactionId != null) {
            $tx = Transaction::firstWhere('id', $transactionId);
            if (!$tx) {
                return;
            }

            $explode = explode(':', $extrinsicIdentifier);
            if ($tx->transaction_chain_id !== $explode[0]) {
                return;
            }

            if ($explode[1]) {
                $tx->result = SystemEventType::EXTRINSIC_SUCCESS->name;
                Teleport::safeBroadcast(
                    $wallet = Wallet::firstWhere('public_key', $tx->wallet_public_key),
                    $wallet,
                    $tx->amount,
                    currentMatrix()->value,
                    $tx,
                );
            }

            if (!$explode[1]) {
                $tx->result = SystemEventType::EXTRINSIC_FAILED->name;
            }

            $tx->save();
        }
    }

    protected function createDaemonTransaction(string $account, string $amount): void
    {
        $this->info('Creating transaction to teleport ENJ from: ' . $account);

        $managedWallet = Wallet::firstWhere([
            'public_key' => $account,
        ]);

        $transferableAmount = $this->codec->encoder()->compact(
            gmp_strval(gmp_sub($amount, '100000000000000000'))
        );
        $call = '0x630903000100a10f0300010100' . HexConverter::unPrefix($managedWallet->public_key);
        $call .= '030400000000' . HexConverter::unPrefix($transferableAmount);
        $call .= '0000000000';

        $transaction = Transaction::create([
            'wallet_public_key' => $managedWallet->public_key,
            'method' => 'LimitedTeleportAssets',
            'state' => TransactionState::PENDING->name,
            'network' => currentRelay()->name,
            'encoded_data' => $call,
            'idempotency_key' => Str::uuid(),
        ]);

        TransactionCreated::safeBroadcast(
            transaction: $transaction
        );

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
