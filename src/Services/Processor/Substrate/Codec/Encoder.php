<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec;

use Codec\Base;
use Codec\ScaleBytes;
use Codec\Types\ScaleInstance;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Clients\Implementations\SubstrateWebsocket;
use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Enums\Substrate\StorageKey;
use Enjin\Platform\Models\Substrate\RoyaltyPolicyParams;
use Enjin\Platform\Support\Blake2;
use Enjin\Platform\Support\Metadata;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class Encoder
{
    public ScaleInstance $scaleInstance;
    protected static array $callIndexes = [];

    protected static array $callIndexKeys = [
        'Batch' => 'MatrixUtility.batch',
        'TransferBalance' => 'Balances.transfer',
        'TransferBalanceKeepAlive' => 'Balances.transfer_keep_alive',
        'TransferAllBalance' => 'Balances.transfer_all',
        'ApproveCollection' => 'MultiTokens.approve_collection',
        'UnapproveCollection' => 'MultiTokens.unapprove_collection',
        'ApproveToken' => 'MultiTokens.approve_token',
        'UnapproveToken' => 'MultiTokens.unapprove_token',
        'BatchSetAttribute' => 'MultiTokens.batch_set_attribute',
        'BatchTransfer' => 'MultiTokens.batch_transfer',
        'Transfer' => 'MultiTokens.transfer',
        'CreateCollection' => 'MultiTokens.create_collection',
        'DestroyCollection' => 'MultiTokens.destroy_collection',
        'MutateCollection' => 'MultiTokens.mutate_collection',
        'MutateToken' => 'MultiTokens.mutate_token',
        'Mint' => 'MultiTokens.mint',
        'BatchMint' => 'MultiTokens.batch_mint',
        'Burn' => 'MultiTokens.burn',
        'Freeze' => 'MultiTokens.freeze',
        'Thaw' => 'MultiTokens.thaw',
        'SetRoyalty' => 'MultiTokens.set_royalty',
        'SetAttribute' => 'MultiTokens.set_attribute',
        'RemoveAttribute' => 'MultiTokens.remove_attribute',
        'RemoveAllAttributes' => 'MultiTokens.remove_all_attributes',
    ];

    public function __construct(ScaleInstance $scaleInstance)
    {
        $this->scaleInstance = $scaleInstance;
        static::$callIndexes = $this->loadCallIndexes();
    }

    public static function getCallIndexKeys(): array
    {
        return static::$callIndexKeys;
    }

    public static function setCallIndexKeys(array $keys): void
    {
        static::$callIndexKeys = $keys;
    }

    public function methodSupported($method): bool
    {
        return array_key_exists($method, static::$callIndexKeys);
    }

    public function uint32(string $value): string
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('u32')->encode($value);

        return HexConverter::prefix($encoded);
    }

    public function compact(string $value): string
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('Compact<u32>')->encode($value);

        return HexConverter::prefix($encoded);
    }

    public function sequenceLength(string $sequence): string
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('Compact<u32>')->encode(count(HexConverter::hexToBytes($sequence)));

        return HexConverter::prefix($encoded);
    }

    public function signingPayload(
        string $call,
        int $nonce,
        string $blockHash,
        string $genesisHash,
        int $specVersion,
        int $txVersion,
        ?string $era = '00',
        ?string $tip = '00',
    ): string {
        $call = HexConverter::unPrefix($call);
        $nonce = HexConverter::unPrefix($this->compact(gmp_strval($nonce)));
        $blockHash = HexConverter::unPrefix($blockHash);
        $genesisHash = HexConverter::unPrefix($genesisHash);
        $specVersion = HexConverter::unPrefix($this->uint32(gmp_strval($specVersion)));
        $txVersion = HexConverter::unPrefix($this->uint32(gmp_strval($txVersion)));
        $era = HexConverter::unPrefix($era);
        $tip = $tip == '0' ? '00' : HexConverter::unPrefix($this->compact(gmp_strval($tip)));

        return HexConverter::prefix($call . $era . $nonce . $tip . $specVersion . $txVersion . $genesisHash . $blockHash);
    }

    public function signingPayloadJSON(
        string $call,
        string $address,
        int $nonce,
        string $blockHash,
        string $genesisHash,
        int $specVersion,
        int $txVersion,
        ?string $era = '00',
        ?string $tip = '00',
    ): array {
        return [
            'address' => SS58Address::encode($address),
            'blockHash' => $blockHash,
            'blockNumber' => '0x00000000',
            'era' => HexConverter::prefix($era),
            'genesisHash' => $genesisHash,
            'method' => $call,
            'nonce' => HexConverter::intToHexPrefixed($nonce),
            'signedExtensions' => [
                'CheckNonZeroSender',
                'CheckSpecVersion',
                'CheckTxVersion',
                'CheckGenesis',
                'CheckMortality',
                'CheckNonce',
                'CheckWeight',
                'ChargeTransactionPayment',
            ],
            'specVersion' => gmp_strval($specVersion),
            'tip' => $this->compact(gmp_strval($tip)),
            'transactionVersion' => gmp_strval($txVersion),
            'version' => 4,
        ];
    }

    public function addSignature(
        string $signer,
        string $signature,
        string $call,
        ?string $nonce = '00',
        ?string $era = '00',
        ?string $tip = '00'
    ): string {
        $nonce = HexConverter::unPrefix($this->compact(HexConverter::hexToInt($nonce)));
        $extrinsic = '84'; // Extra byte
        $extrinsic .= '00' . HexConverter::unPrefix(SS58Address::getPublicKey($signer)); // MultiAddress
        $extrinsic .= HexConverter::unPrefix($signature);
        $extrinsic .= HexConverter::unPrefix($era) . $nonce;
        $extrinsic .= HexConverter::unPrefix($tip) . HexConverter::unPrefix($call);

        return $this->sequenceLength($extrinsic) . $extrinsic;
    }

    public function addFakeSignature(string $call): string
    {
        $extraByte = '84';
        $signer = '006802f945419791d3138b4086aa0b2700abb679f950e2721fd7d65b5d1fdf8f02';
        $signature = '01d19e04fc1a4ec115ec55d29e53676ddaeae0467134f9513b29ed3cd6fd6cd551a96c35b92b867dfd08ba37417e5733620acc4ad17c1d7c65909d6edaaffd4d0e';
        $era = '00';
        $nonce = '00';
        $tip = '00';

        $extrinsic = $extraByte . $signer . $signature . $era . $nonce . $tip . HexConverter::unPrefix($call);

        return $this->sequenceLength($extrinsic) . $extrinsic;
    }

    public function getEncoded(string $type, array $params): string
    {
        if ($type == 'Batch' || (isset($params['continueOnFailure']) && $params['continueOnFailure'] === true)) {
            return static::batch($params['calls'], $params['continueOnFailure']);
        }

        $encoded = $this->scaleInstance->createTypeByTypeString($type)->encode([
            'callIndex' => static::getCallIndex(static::$callIndexKeys[$type]),
            ...$params,
        ]);

        return HexConverter::prefix($encoded);
    }

    public function systemAccountStorageKey(string $publicKey): string
    {
        $publicKey = HexConverter::unPrefix($publicKey);
        $keyHashed = Blake2::hash($publicKey, 128);
        $key = StorageKey::systemAccount()->value . $keyHashed . $publicKey;

        return HexConverter::prefix($key);
    }

    public function batch(array $calls, bool $continueOnFailure): string
    {
        $callIndex = static::$callIndexes['MatrixUtility.batch'];
        $numberOfCalls = $this->scaleInstance->createTypeByTypeString('Compact')->encode(count($calls));
        $calls = str_replace('0x', '', implode('', $calls));
        $continueOnFailure = $continueOnFailure ? '01' : '00';
        $encoded = $callIndex . $numberOfCalls . $calls . $continueOnFailure;

        return HexConverter::prefix($encoded);
    }

    public static function collectionStorageKey(string $collectionId): string
    {
        $hashAndEncode = Blake2::hashAndEncode($collectionId);
        $key = StorageKey::collections()->value . $hashAndEncode;

        return HexConverter::prefix($key);
    }

    public static function tokenStorageKey(string $collectionId, ?string $tokenId = null): string
    {
        $key = StorageKey::tokens()->value . Blake2::hashAndEncode($collectionId);

        if ($tokenId) {
            $key .= Blake2::hashAndEncode($tokenId);
        }

        return HexConverter::prefix($key);
    }

    public static function collectionAccountStorageKey(string $collectionId, ?string $accountId = null): string
    {
        $key = StorageKey::collectionAccounts()->value . Blake2::hashAndEncode($collectionId);

        if ($accountId) {
            $accountId = HexConverter::unPrefix($accountId);
            $key .= Blake2::hash($accountId, 128) . $accountId;
        }

        return HexConverter::prefix($key);
    }

    public static function attributeStorageKey(string $collectionId, ?string $tokenId = null, ?string $key = null): string
    {
        $storageKey = StorageKey::attributes()->value . Blake2::hashAndEncode($collectionId);
        $codec = new ScaleInstance(Base::create());

        if ($tokenId) {
            $encodedToken = $codec->createTypeByTypeString('Option<u128>')->encode($tokenId);
            $storageKey .= Blake2::hash($encodedToken, 128) . $encodedToken;
        }

        if ($key) {
            $encodedKey = $codec->createTypeByTypeString('Bytes')->encode($key);
            $storageKey .= Blake2::hash($encodedKey, 128) . $encodedKey;
        }

        return HexConverter::prefix($storageKey);
    }

    public static function tokenAccountStorageKey(string $collectionId, ?string $tokenId = null, ?string $accountId = null): string
    {
        $key = StorageKey::tokenAccounts()->value . Blake2::hashAndEncode($collectionId);

        if ($tokenId) {
            $key .= Blake2::hashAndEncode($tokenId);
        }

        if ($accountId) {
            $accountId = HexConverter::unPrefix($accountId);
            $key .= Blake2::hash($accountId, 128) . $accountId;
        }

        return HexConverter::prefix($key);
    }

    public function setRoyalty(string $collectionId, ?string $tokenId, RoyaltyPolicyParams $royalty): string
    {
        return static::getEncoded('SetRoyalty', [
            'collectionId' => gmp_init($collectionId),
            'tokenId' => $tokenId,
            'descriptor' => $royalty->toEncodable(),
        ]);
    }

    public function attributeStorage(int $module, int $method): string
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('AttributeStorage')->encode([
            'module' => $module,
            'method' => $method,
        ]);

        return HexConverter::prefix($encoded);
    }

    public static function getCallIndex(string $call, bool $raw = false): array|int|string
    {
        if ($raw) {
            return static::$callIndexes[$call];
        }

        $index = str_split(static::$callIndexes[$call], 2);

        return [HexConverter::hexToInt($index[0]), HexConverter::hexToInt($index[1])];
    }

    protected function loadCallIndexes(): array
    {
        $metadata = Cache::remember(PlatformCache::METADATA->key(), 3600, function () {
            if (app()->runningUnitTests()) {
                return Metadata::v1000();
            }

            $blockchain = new SubstrateWebsocket();
            $response = $blockchain->send('state_getMetadata');
            $blockchain->close();

            return $response;
        });

        if (!$metadata) {
            return [];
        }

        return Cache::rememberForever(
            PlatformCache::CALL_INDEXES->key(config('enjin-platform.chains.selected') . config('enjin-platform.chains.network')),
            function () use ($metadata) {
                $decode = $this->scaleInstance->process('metadata', new ScaleBytes($metadata));

                $callIndexes = collect(Arr::get($decode, 'metadata.call_index'))->mapWithKeys(
                    fn ($call, $key) => [
                        sprintf('%s.%s', Arr::get($call, 'module.name'), Arr::get($call, 'call.name')) => $key,
                    ]
                );

                return $callIndexes->toArray();
            }
        );
    }
}
