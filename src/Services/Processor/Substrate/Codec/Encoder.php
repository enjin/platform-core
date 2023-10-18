<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec;

use Codec\ScaleBytes;
use Codec\Types\ScaleInstance;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Clients\Implementations\SubstrateWebsocket;
use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Enums\Substrate\StorageKey;
use Enjin\Platform\Models\Substrate\BurnParams;
use Enjin\Platform\Models\Substrate\CreateTokenParams;
use Enjin\Platform\Models\Substrate\FreezeTypeParams;
use Enjin\Platform\Models\Substrate\MintParams;
use Enjin\Platform\Models\Substrate\MintPolicyParams;
use Enjin\Platform\Models\Substrate\OperatorTransferParams;
use Enjin\Platform\Models\Substrate\RoyaltyPolicyParams;
use Enjin\Platform\Models\Substrate\SimpleTransferParams;
use Enjin\Platform\Models\Substrate\TokenMarketBehaviorParams;
use Enjin\Platform\Support\Blake2;
use Enjin\Platform\Support\Metadata;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class Encoder
{
    protected static array $callIndexes = [];

    protected static ScaleInstance $scaleInstance;

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
        static::$scaleInstance = $scaleInstance;
        static::$callIndexes = $this->loadCallIndexes();
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
        $encoded = static::$scaleInstance->createTypeByTypeString('Compact<u32>')->encode(count(HexConverter::hexToBytes($sequence)));

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

    public static function getEncoded(string $type, array $params): string
    {
        $encoded = static::$scaleInstance->createTypeByTypeString($type)->encode([
            'callIndex' => static::getCallIndex(static::$callIndexKeys[$type]),
            ...$params,
        ]);

        return HexConverter::prefix($encoded);
    }

    public function transferBalance(string $recipient, string $value): string
    {
        return static::getEncoded('TransferBalance', [
            'dest' => [
                'Id' => HexConverter::unPrefix($recipient),
            ],
            'value' => gmp_init($value),
        ]);
    }

    public function transferBalanceKeepAlive(string $recipient, string $value): string
    {
        return static::getEncoded('TransferBalanceKeepAlive', [
            'dest' => [
                'Id' => HexConverter::unPrefix($recipient),
            ],
            'value' => gmp_init($value),
        ]);
    }

    public function transferAllBalance(string $recipient, ?bool $keepAlive = false): string
    {
        return static::getEncoded('TransferAllBalance', [
            'dest' => [
                'Id' => HexConverter::unPrefix($recipient),
            ],
            'keepAlive' => $keepAlive,
        ]);
    }

    public function systemAccountStorageKey(string $publicKey): string
    {
        $publicKey = HexConverter::unPrefix($publicKey);
        $keyHashed = Blake2::hash($publicKey, 128);
        $key = StorageKey::SYSTEM_ACCOUNT->value . $keyHashed . $publicKey;

        return HexConverter::prefix($key);
    }

    public function approveCollection(string $collectionId, string $operator, ?int $expiration = null): string
    {
        return static::getEncoded('ApproveCollection', [
            'collectionId' => gmp_init($collectionId),
            'operator' => HexConverter::unPrefix($operator),
            'expiration' => $expiration,
        ]);
    }

    public function unapproveCollection(string $collectionId, string $operator): string
    {
        return static::getEncoded('UnapproveCollection', [
            'collectionId' => gmp_init($collectionId),
            'operator' => HexConverter::unPrefix($operator),
        ]);
    }

    public function approveToken(string $collectionId, string $tokenId, string $operator, string $amount, string $currentAmount, ?int $expiration = null): string
    {
        return static::getEncoded('ApproveToken', [
            'collectionId' => gmp_init($collectionId),
            'tokenId' => gmp_init($tokenId),
            'operator' => HexConverter::unPrefix($operator),
            'amount' => gmp_init($amount),
            'expiration' => $expiration,
            'currentAmount' => gmp_init($currentAmount),
        ]);
    }

    public function unapproveToken(string $collectionId, string $tokenId, string $operator): string
    {
        return static::getEncoded('UnapproveToken', [
            'collectionId' => gmp_init($collectionId),
            'tokenId' => gmp_init($tokenId),
            'operator' => HexConverter::unPrefix($operator),
        ]);
    }

    public function batch(array $calls, bool $continueOnFailure): string
    {
        $callIndex = static::$callIndexes['MatrixUtility.batch'];
        $numberOfCalls = static::$scaleInstance->createTypeByTypeString('Compact')->encode(count($calls));
        $calls = str_replace('0x', '', implode('', $calls));
        $continueOnFailure = $continueOnFailure ? '01' : '00';
        $encoded = $callIndex . $numberOfCalls . $calls . $continueOnFailure;

        return HexConverter::prefix($encoded);
    }

    public function batchSetAttribute(string $collectionId, ?string $tokenId, array $attributes)
    {
        return static::getEncoded('BatchSetAttribute', [
            'collectionId' => gmp_init($collectionId),
            'tokenId' => $tokenId !== null ? gmp_init($tokenId) : null,
            'attributes' => array_map(
                fn ($attribute) => [
                    'key' => HexConverter::stringToHexPrefixed($attribute['key']),
                    'value' => HexConverter::stringToHexPrefixed($attribute['value']),
                ],
                $attributes
            ),
        ]);
    }

    public function batchTransfer(string $collectionId, array $recipients)
    {
        return static::getEncoded('BatchTransfer', [
            'collectionId' => gmp_init($collectionId),
            'recipients' => array_map(
                fn ($item) => [
                    'accountId' => HexConverter::unPrefix($item['accountId']),
                    'params' => $item['params']->toEncodable(),
                ],
                $recipients
            ),
        ]);
    }

    public function transferToken(string $recipient, string $collectionId, SimpleTransferParams|OperatorTransferParams $params): string
    {
        return static::getEncoded('Transfer', [
            'recipient' => [
                'Id' => HexConverter::unPrefix($recipient),
            ],
            'collectionId' => gmp_init($collectionId),
            'params' => $params->toEncodable(),
        ]);
    }

    public function createCollection(MintPolicyParams $mintPolicy, ?RoyaltyPolicyParams $marketPolicy = null, ?array $explicitRoyaltyCurrencies = [], ?array $attributes = []): string
    {
        return static::getEncoded('CreateCollection', [
            'descriptor' => [
                'policy' => [
                    'mint' => $mintPolicy->toEncodable(),
                    'market' => $marketPolicy?->toEncodable(),
                ],
                'explicitRoyaltyCurrencies' => array_map(
                    fn ($multiToken) => [
                        'collectionId' => gmp_init($multiToken['collectionId']),
                        'tokenId' => gmp_init($multiToken['tokenId']),
                    ],
                    $explicitRoyaltyCurrencies
                ),
                'attributes' => array_map(
                    fn ($attribute) => [
                        'key' => HexConverter::stringToHexPrefixed($attribute['key']),
                        'value' => HexConverter::stringToHexPrefixed($attribute['value']),
                    ],
                    $attributes
                ),
            ],
        ]);
    }

    public function destroyCollection(string $collectionId): string
    {
        return static::getEncoded('DestroyCollection', [
            'collectionId' => gmp_init($collectionId),
        ]);
    }

    public function mutateCollection(string $collectionId, ?string $owner = null, null|array|RoyaltyPolicyParams $royalty = null, ?array $explicitRoyaltyCurrencies = null): string
    {
        return static::getEncoded('MutateCollection', [
            'collectionId' => gmp_init($collectionId),
            'mutation' => [
                'owner' => $owner !== null ? HexConverter::unPrefix($owner) : null,
                'royalty' => is_array($royalty) ? ['NoMutation' => null] : ['SomeMutation' => $royalty?->toEncodable()],
                'explicitRoyaltyCurrencies' => $explicitRoyaltyCurrencies !== null ? array_map(
                    fn ($multiToken) => [
                        'collectionId' => gmp_init($multiToken['collectionId']),
                        'tokenId' => gmp_init($multiToken['tokenId']),
                    ],
                    $explicitRoyaltyCurrencies
                ) : null,
            ],
        ]);
    }

    public function mutateToken(string $collectionId, string $tokenId, null|array|TokenMarketBehaviorParams $behavior = null, ?bool $listingForbidden = null): string
    {
        return static::getEncoded('MutateToken', [
            'collectionId' => gmp_init($collectionId),
            'tokenId' => gmp_init($tokenId),
            'mutation' => [
                'behavior' => is_array($behavior) ? ['NoMutation' => null] : ['SomeMutation' => $behavior?->toEncodable()],
                'listingForbidden' => $listingForbidden,
                'metadata' => null,
            ],
        ]);
    }

    public static function collectionStorageKey(string $collectionId): string
    {
        $hashAndEncode = Blake2::hashAndEncode($collectionId);
        $key = StorageKey::COLLECTIONS->value . $hashAndEncode;

        return HexConverter::prefix($key);
    }

    public static function tokenStorageKey(string $collectionId, string $tokenId): string
    {
        $key = StorageKey::TOKENS->value . Blake2::hashAndEncode($collectionId) . Blake2::hashAndEncode($tokenId);

        return HexConverter::prefix($key);
    }

    public static function collectionAccountStorageKey(string $collectionId, string $accountId): string
    {
        $accountId = HexConverter::unPrefix($accountId);
        $key = StorageKey::COLLECTION_ACCOUNTS->value . Blake2::hashAndEncode($collectionId) . Blake2::hash($accountId, 128) . $accountId;

        return HexConverter::prefix($key);
    }

    public function attributeStorageKey(string $collectionId, ?string $tokenId, string $key): string
    {
        $storageKey = StorageKey::ATTRIBUTES->value . Blake2::hashAndEncode($collectionId);

        $encodedToken = static::$scaleInstance->createTypeByTypeString('Option<u128>')->encode($tokenId);
        $storageKey .= Blake2::hash($encodedToken, 128) . $encodedToken;

        $encodedKey = static::$scaleInstance->createTypeByTypeString('Bytes')->encode($key);
        $storageKey .= Blake2::hash($encodedKey, 128) . $encodedKey;

        return HexConverter::prefix($storageKey);
    }

    public static function tokenAccountStorageKey(string $accountId, string $collectionId, string $tokenId): string
    {
        $accountId = HexConverter::unPrefix($accountId);
        $key = StorageKey::TOKEN_ACCOUNTS->value . Blake2::hashAndEncode($collectionId) . Blake2::hashAndEncode($tokenId) . Blake2::hash($accountId, 128) . $accountId;

        return HexConverter::prefix($key);
    }

    public function mint(string $recipientId, string $collectionId, CreateTokenParams|MintParams $params): string
    {
        return static::getEncoded('Mint', [
            'recipient' => [
                'Id' => HexConverter::unPrefix($recipientId),
            ],
            'collectionId' => gmp_init($collectionId),
            'params' => $params->toEncodable(),
        ]);
    }

    public function batchMint(string $collectionId, array $recipients)
    {
        return static::getEncoded('BatchMint', [
            'collectionId' => gmp_init($collectionId),
            'recipients' => array_map(
                fn ($item) => [
                    'accountId' => HexConverter::unPrefix($item['accountId']),
                    'params' => $item['params']->toEncodable(),
                ],
                $recipients
            ),
        ]);
    }

    public function burn(string $collectionId, BurnParams $params): string
    {
        return static::getEncoded('Burn', [
            'collectionId' => gmp_init($collectionId),
            'params' => $params->toEncodable(),
        ]);
    }

    public function freeze(string $collectionId, FreezeTypeParams $params): string
    {
        return static::getEncoded('Freeze', [
            'collectionId' => gmp_init($collectionId),
            'freezeType' => $params->toEncodable(),
        ]);
    }

    public function thaw(string $collectionId, FreezeTypeParams $params): string
    {
        return static::getEncoded('Thaw', [
            'collectionId' => gmp_init($collectionId),
            'freezeType' => $params->toEncodable(),
        ]);
    }

    public function setRoyalty(string $collectionId, ?string $tokenId, RoyaltyPolicyParams $royalty): string
    {
        return static::getEncoded('SetRoyalty', [
            'collectionId' => gmp_init($collectionId),
            'tokenId' => $tokenId,
            'descriptor' => $royalty->toEncodable(),
        ]);
    }

    public function setAttribute(string $collectionId, ?string $tokenId, string $key, string $value): string
    {
        return static::getEncoded('SetAttribute', [
            'collectionId' => gmp_init($collectionId),
            'tokenId' => $tokenId !== null ? gmp_init($tokenId) : null,
            'key' => HexConverter::stringToHexPrefixed($key),
            'value' => HexConverter::stringToHexPrefixed($value),
        ]);
    }

    public function removeAttribute(string $collectionId, ?string $tokenId, string $key): string
    {
        return static::getEncoded('RemoveAttribute', [
            'collectionId' => gmp_init($collectionId),
            'tokenId' => $tokenId !== null ? gmp_init($tokenId) : null,
            'key' => HexConverter::stringToHex($key),
        ]);
    }

    public function removeAllAttributes(string $collectionId, ?string $tokenId, int $attributeCount): string
    {
        return static::getEncoded('RemoveAllAttributes', [
            'collectionId' => gmp_init($collectionId),
            'tokenId' => $tokenId !== null ? gmp_init($tokenId) : null,
            'attributeCount' => $attributeCount,
        ]);
    }

    public function attributeStorage(int $module, int $method): string
    {
        $encoded = static::$scaleInstance->createTypeByTypeString('AttributeStorage')->encode([
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
                return Metadata::v604();
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
                $decode = static::$scaleInstance->process('metadata', new ScaleBytes($metadata));

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
