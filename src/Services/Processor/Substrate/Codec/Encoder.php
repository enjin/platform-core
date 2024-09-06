<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec;

use Codec\Base;
use Codec\ScaleBytes;
use Codec\Types\ScaleInstance;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Clients\Implementations\SubstrateWebsocket;
use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Enums\Substrate\StorageType;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\Substrate\RoyaltyPolicyParams;
use Enjin\Platform\Support\Blake2;
use Enjin\Platform\Support\Hex;
use Enjin\Platform\Support\Metadata;
use Enjin\Platform\Support\SS58Address;
use Enjin\Platform\Support\Twox;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class Encoder
{
    protected static array $callIndexes = [];

    protected static array $callIndexKeys = [
        'Batch' => 'MatrixUtility.batch',
        'TransferBalance' => 'Balances.transfer_keep_alive',
        'TransferBalanceKeepAlive' => 'Balances.transfer_keep_alive',
        'TransferAllBalance' => 'Balances.transfer_all',
        'ApproveCollection' => 'MultiTokens.approve_collection',
        'UnapproveCollection' => 'MultiTokens.unapprove_collection',
        'ApproveToken' => 'MultiTokens.approve_token',
        'UnapproveToken' => 'MultiTokens.unapprove_token',
        'BatchSetAttribute' => 'MultiTokens.batch_set_attribute',
        'BatchSetAttributeV1010' => 'MultiTokens.batch_set_attribute',
        'BatchTransfer' => 'MultiTokens.batch_transfer',
        'BatchTransferV1010' => 'MultiTokens.batch_transfer',
        'Transfer' => 'MultiTokens.transfer',
        'TransferV1010' => 'MultiTokens.transfer',
        'CreateCollection' => 'MultiTokens.create_collection',
        'CreateCollectionV1010' => 'MultiTokens.create_collection',
        'DestroyCollection' => 'MultiTokens.destroy_collection',
        'MutateCollection' => 'MultiTokens.mutate_collection',
        'MutateToken' => 'MultiTokens.mutate_token',
        'MutateTokenV1010' => 'MultiTokens.mutate_token',
        'Mint' => 'MultiTokens.mint',
        'MintV1010' => 'MultiTokens.mint',
        'BatchMint' => 'MultiTokens.batch_mint',
        'BatchMintV1010' => 'MultiTokens.batch_mint',
        'Burn' => 'MultiTokens.burn',
        'BurnV1010' => 'MultiTokens.burn',
        'Freeze' => 'MultiTokens.freeze',
        'Thaw' => 'MultiTokens.thaw',
        'SetRoyalty' => 'MultiTokens.set_royalty',
        'SetAttribute' => 'MultiTokens.set_attribute',
        'SetAttributeV1010' => 'MultiTokens.set_attribute',
        'RemoveAttribute' => 'MultiTokens.remove_attribute',
        'RemoveAllAttributes' => 'MultiTokens.remove_all_attributes',
        'AcceptCollectionTransfer' => 'MultiTokens.accept_collection_transfer',
    ];

    protected static array $overrideCallIndex = [
        'MatrixUtility.batch' => [57, 0],
        'Balances.transfer' => [10, 0],
        'Balances.transfer_keep_alive' => [10, 3],
        'Balances.transfer_all' => [10, 4],
        'MultiTokens.approve_collection' => [40, 15],
        'MultiTokens.unapprove_collection' => [40, 16],
        'MultiTokens.approve_token' => [40, 17],
        'MultiTokens.unapprove_token' => [40, 18],
        'MultiTokens.batch_set_attribute' => [40, 14],
        'MultiTokens.batch_transfer' => [40, 12],
        'MultiTokens.transfer' => [40, 6],
        'MultiTokens.create_collection' => [40, 0],
        'MultiTokens.destroy_collection' => [40, 1],
        'MultiTokens.mutate_collection' => [40, 2],
        'MultiTokens.mutate_token' => [40, 3],
        'MultiTokens.mint' => [40, 4],
        'MultiTokens.batch_mint' => [40, 13],
        'MultiTokens.burn' => [40, 5],
        'MultiTokens.freeze' => [40, 7],
        'MultiTokens.thaw' => [40, 8],
        'MultiTokens.set_attribute' => [40, 9],
        'MultiTokens.remove_attribute' => [40, 10],
        'MultiTokens.remove_all_attributes' => [40, 11],
        'MultiTokens.accept_collection_transfer' => [40, 41],
    ];

    public function __construct(public ScaleInstance $scaleInstance)
    {
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
        $encoded = $this->scaleInstance->createTypeByTypeString('Compact')->encode($value);

        return HexConverter::prefix($encoded);
    }

    public function sequenceLength(string $sequence): string
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('Compact<u32>')->encode(count(HexConverter::hexToBytes($sequence)));

        return HexConverter::prefix($encoded);
    }

    public function encodeRaw(string $type, array $data): string
    {
        return $this->scaleInstance->createTypeByTypeString($type)->encode($data);
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
        ?string $mode = '00',
        ?string $metadataHash = '00',
    ): string {
        $call = HexConverter::unPrefix($call);
        $nonce = HexConverter::unPrefix($this->compact(gmp_strval($nonce)));
        $blockHash = HexConverter::unPrefix($blockHash);
        $genesisHash = HexConverter::unPrefix($genesisHash);
        $specVersion = HexConverter::unPrefix($this->uint32(gmp_strval($specVersion)));
        $txVersion = HexConverter::unPrefix($this->uint32(gmp_strval($txVersion)));
        $era = HexConverter::unPrefix($era);
        $tip = $tip == '0' ? '00' : HexConverter::unPrefix($this->compact(gmp_strval($tip)));
        $mode = networkConfig('spec-version') >= 1010 ? HexConverter::unPrefix($mode) : '';
        $metadataHash = networkConfig('spec-version') >= 1010 ? HexConverter::unPrefix($metadataHash) : '';

        return HexConverter::prefix($call . $era . $nonce . $tip . $mode . $specVersion . $txVersion . $genesisHash . $blockHash . $metadataHash);
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
        ?string $mode = '00',
        ?string $metadataHash = '00',
    ): array {
        return array_merge(
            [
                'address' => SS58Address::encode($address),
                'blockHash' => $blockHash,
                'blockNumber' => '0x00000000',
                'era' => HexConverter::prefix($era),
                'genesisHash' => $genesisHash,
                'method' => $call,
                'nonce' => HexConverter::intToHexPrefixed($nonce),
                'signedExtensions' => array_merge([
                    'CheckSpecVersion',
                    'CheckTxVersion',
                    'CheckGenesis',
                    'CheckMortality',
                    'CheckNonce',
                    'CheckWeight',
                    'ChargeTransactionPayment',
                    'CheckFuelTank',
                ], networkConfig('spec-version') >= 1010 ? ['CheckMetadataHash'] : []),
                'specVersion' => gmp_strval($specVersion),
                'tip' => $this->compact(gmp_strval($tip)),
                'transactionVersion' => gmp_strval($txVersion),
                'version' => 4,
            ],
            networkConfig('spec-version') >= 1010
            ? [
                'mode' => $mode,
                'metadataHash' => $metadataHash,
            ]
            : []
        );
    }

    public function addSignature(
        string $signer,
        string $signature,
        string $call,
        ?string $nonce = '00',
        ?string $era = '00',
        ?string $tip = '00',
        ?string $mode = '00'
    ): string {
        $nonce = $this->compact(HexConverter::hexToInt($nonce));

        $extrinsic = '84'; // Extra byte - Meaning it is a signed transaction
        $extrinsic .= '00' . HexConverter::unPrefix(SS58Address::getPublicKey($signer)); // MultiAddress
        $extrinsic .= HexConverter::unPrefix($signature);
        $extrinsic .= HexConverter::unPrefix($era);
        $extrinsic .= HexConverter::unPrefix($nonce);
        $extrinsic .= HexConverter::unPrefix($tip);

        if (networkConfig('spec-version') >= 1010) {
            $extrinsic .= HexConverter::unPrefix($mode);
        }

        $extrinsic .= HexConverter::unPrefix($call);

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

        $extrinsic = $extraByte . $signer . $signature . $era . $nonce . $tip;

        if (networkConfig('spec-version') >= 1010) {
            $extrinsic .= HexConverter::unPrefix('00');
        }

        $extrinsic .= HexConverter::unPrefix($call);

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

        $hex = HexConverter::prefix($encoded);
        if (preg_match('/^0x[a-fA-F0-9]*$/', $hex) >= 1) {
            return $hex;
        }

        throw new PlatformException('Invalid encoded data: '.$hex);
    }

    public function systemAccountStorageKey(string $publicKey): string
    {
        $publicKey = HexConverter::unPrefix($publicKey);
        $keyHashed = Blake2::hash($publicKey, 128);
        $key = StorageType::SYSTEM_ACCOUNT->value . $keyHashed . $publicKey;

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
        $key = StorageType::COLLECTIONS->value . $hashAndEncode;

        return HexConverter::prefix($key);
    }

    public static function pendingCollectionTransfersStorageKey(string $collectionId): string
    {
        $hasher = new Twox();

        $hexedNumber = HexConverter::uintToHex($collectionId, 32);
        $reversed = Hex::reverseEndian($hexedNumber);
        $hashAndEncode = $hasher->ByHasherName('Twox64Concat', HexConverter::prefix($reversed));
        $key = StorageType::PENDING_COLLECTION_TRANSFERS->value . $hashAndEncode;

        return HexConverter::prefix($key);
    }

    public static function tokenStorageKey(string $collectionId, ?string $tokenId = null): string
    {
        $key = StorageType::TOKENS->value . Blake2::hashAndEncode($collectionId);

        if ($tokenId) {
            $key .= Blake2::hashAndEncode($tokenId);
        }

        return HexConverter::prefix($key);
    }

    public static function collectionAccountStorageKey(string $collectionId, ?string $accountId = null): string
    {
        $key = StorageType::COLLECTION_ACCOUNTS->value . Blake2::hashAndEncode($collectionId);

        if ($accountId) {
            $accountId = HexConverter::unPrefix($accountId);
            $key .= Blake2::hash($accountId, 128) . $accountId;
        }

        return HexConverter::prefix($key);
    }

    public static function attributeStorageKey(string $collectionId, ?string $tokenId = null, ?string $key = null): string
    {
        $storageKey = StorageType::ATTRIBUTES->value . Blake2::hashAndEncode($collectionId);
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
        $key = StorageType::TOKEN_ACCOUNTS->value . Blake2::hashAndEncode($collectionId);

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

        if (isset(static::$overrideCallIndex[$call])) {
            return static::$overrideCallIndex[$call];
        }

        $index = str_split((string) static::$callIndexes[$call], 2);

        return [HexConverter::hexToInt($index[0]), HexConverter::hexToInt($index[1])];
    }

    protected function loadCallIndexes(): array
    {
        $metadata = Cache::remember(PlatformCache::METADATA->key(), 3600, function () {
            if (app()->runningUnitTests()) {
                return Metadata::v1006();
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
            PlatformCache::CALL_INDEXES->key(network()->value),
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
