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
    public array $callIndexes;

    protected ScaleInstance $scaleInstance;

    public function __construct(ScaleInstance $scaleInstance)
    {
        $this->scaleInstance = $scaleInstance;
        $this->callIndexes = $this->loadCallIndexes();
    }

    public function sequenceLength(string $sequence): string
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('Compact<u32>')->encode(count(HexConverter::hexToBytes($sequence)));

        return HexConverter::prefix($encoded);
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

    public function transferBalance(string $recipient, string $value): string
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('TransferBalance')->encode([
            'callIndex' => $this->getCallIndex('Balances.transfer'),
            'dest' => [
                'Id' => HexConverter::unPrefix($recipient),
            ],
            'value' => gmp_init($value),
        ]);

        return HexConverter::prefix($encoded);
    }

    public function transferBalanceKeepAlive(string $recipient, string $value): string
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('TransferBalanceKeepAlive')->encode([
            'callIndex' => $this->getCallIndex('Balances.transfer_keep_alive'),
            'dest' => [
                'Id' => HexConverter::unPrefix($recipient),
            ],
            'value' => gmp_init($value),
        ]);

        return HexConverter::prefix($encoded);
    }

    public function transferAllBalance(string $recipient, ?bool $keepAlive = false): string
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('TransferAllBalance')->encode([
            'callIndex' => $this->getCallIndex('Balances.transfer_all'),
            'dest' => [
                'Id' => HexConverter::unPrefix($recipient),
            ],
            'keepAlive' => $keepAlive,
        ]);

        return HexConverter::prefix($encoded);
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
        $encoded = $this->scaleInstance->createTypeByTypeString('ApproveCollection')->encode([
            'callIndex' => $this->getCallIndex('MultiTokens.approve_collection'),
            'collectionId' => gmp_init($collectionId),
            'operator' => HexConverter::unPrefix($operator),
            'expiration' => $expiration,
        ]);

        return HexConverter::prefix($encoded);
    }

    public function unapproveCollection(string $collectionId, string $operator): string
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('UnapproveCollection')->encode([
            'callIndex' => $this->getCallIndex('MultiTokens.unapprove_collection'),
            'collectionId' => gmp_init($collectionId),
            'operator' => HexConverter::unPrefix($operator),
        ]);

        return HexConverter::prefix($encoded);
    }

    public function approveToken(string $collectionId, string $tokenId, string $operator, string $amount, string $currentAmount, ?int $expiration = null): string
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('ApproveToken')->encode([
            'callIndex' => $this->getCallIndex('MultiTokens.approve_token'),
            'collectionId' => gmp_init($collectionId),
            'tokenId' => gmp_init($tokenId),
            'operator' => HexConverter::unPrefix($operator),
            'amount' => gmp_init($amount),
            'expiration' => $expiration,
            'currentAmount' => gmp_init($currentAmount),
        ]);

        return HexConverter::prefix($encoded);
    }

    public function unapproveToken(string $collectionId, string $tokenId, string $operator): string
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('UnapproveToken')->encode([
            'callIndex' => $this->getCallIndex('MultiTokens.unapprove_token'),
            'collectionId' => gmp_init($collectionId),
            'tokenId' => gmp_init($tokenId),
            'operator' => HexConverter::unPrefix($operator),
        ]);

        return HexConverter::prefix($encoded);
    }

    public function batch(array $calls, bool $continueOnFailure): string
    {
        $callIndex = $this->callIndexes['MatrixUtility.batch'];
        $numberOfCalls = $this->scaleInstance->createTypeByTypeString('Compact')->encode(count($calls));
        $calls = str_replace('0x', '', implode('', $calls));
        $continueOnFailure = $continueOnFailure ? '01' : '00';
        $encoded = $callIndex . $numberOfCalls . $calls . $continueOnFailure;

        return HexConverter::prefix($encoded);
    }

    public function batchSetAttribute(string $collectionId, ?string $tokenId, array $attributes)
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('BatchSetAttribute')->encode([
            'callIndex' => $this->getCallIndex('MultiTokens.batch_set_attribute'),
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

        return HexConverter::prefix($encoded);
    }

    public function batchTransfer(string $collectionId, array $recipients)
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('BatchTransfer')->encode([
            'callIndex' => $this->getCallIndex('MultiTokens.batch_transfer'),
            'collectionId' => gmp_init($collectionId),
            'recipients' => array_map(
                fn ($item) => [
                    'accountId' => HexConverter::unPrefix($item['accountId']),
                    'params' => $item['params']->toEncodable(),
                ],
                $recipients
            ),
        ]);

        return HexConverter::prefix($encoded);
    }

    public function transferToken(string $recipient, string $collectionId, SimpleTransferParams|OperatorTransferParams $params): string
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('Transfer')->encode([
            'callIndex' => $this->getCallIndex('MultiTokens.transfer'),
            'recipient' => [
                'Id' => HexConverter::unPrefix($recipient),
            ],
            'collectionId' => gmp_init($collectionId),
            'params' => $params->toEncodable(),
        ]);

        return HexConverter::prefix($encoded);
    }

    public function createCollection(MintPolicyParams $mintPolicy, ?RoyaltyPolicyParams $marketPolicy = null, ?array $explicitRoyaltyCurrencies = [], ?array $attributes = []): string
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('CreateCollection')->encode(
            [
                'callIndex' => $this->getCallIndex('MultiTokens.create_collection'),
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
            ]
        );

        return HexConverter::prefix($encoded);
    }

    public function destroyCollection(string $collectionId): string
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('DestroyCollection')->encode([
            'callIndex' => $this->getCallIndex('MultiTokens.destroy_collection'),
            'collectionId' => gmp_init($collectionId),
        ]);

        return HexConverter::prefix($encoded);
    }

    public function mutateCollection(string $collectionId, ?string $owner = null, null|array|RoyaltyPolicyParams $royalty = null, ?array $explicitRoyaltyCurrencies = null): string
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('MutateCollection')->encode([
            'callIndex' => $this->getCallIndex('MultiTokens.mutate_collection'),
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

        return HexConverter::prefix($encoded);
    }

    public function mutateToken(string $collectionId, string $tokenId, null|array|TokenMarketBehaviorParams $behavior = null, ?bool $listingForbidden = null): string
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('MutateToken')->encode([
            'callIndex' => $this->getCallIndex('MultiTokens.mutate_token'),
            'collectionId' => gmp_init($collectionId),
            'tokenId' => gmp_init($tokenId),
            'mutation' => [
                'behavior' => is_array($behavior) ? ['NoMutation' => null] : ['SomeMutation' => $behavior?->toEncodable()],
                'listingForbidden' => $listingForbidden,
                'metadata' => null,
            ],
        ]);

        return HexConverter::prefix($encoded);
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

        $encodedToken = $this->scaleInstance->createTypeByTypeString('Option<u128>')->encode($tokenId);
        $storageKey .= Blake2::hash($encodedToken, 128) . $encodedToken;

        $encodedKey = $this->scaleInstance->createTypeByTypeString('Bytes')->encode($key);
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
        $encoded = $this->scaleInstance->createTypeByTypeString('Mint')->encode([
            'callIndex' => $this->getCallIndex('MultiTokens.mint'),
            'recipient' => [
                'Id' => HexConverter::unPrefix($recipientId),
            ],
            'collectionId' => gmp_init($collectionId),
            'params' => $params->toEncodable(),
        ]);

        return HexConverter::prefix($encoded);
    }

    public function batchMint(string $collectionId, array $recipients)
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('BatchMint')->encode([
            'callIndex' => $this->getCallIndex('MultiTokens.batch_mint'),
            'collectionId' => gmp_init($collectionId),
            'recipients' => array_map(
                fn ($item) => [
                    'accountId' => HexConverter::unPrefix($item['accountId']),
                    'params' => $item['params']->toEncodable(),
                ],
                $recipients
            ),
        ]);

        return HexConverter::prefix($encoded);
    }

    public function burn(string $collectionId, BurnParams $params): string
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('Burn')->encode([
            'callIndex' => $this->getCallIndex('MultiTokens.burn'),
            'collectionId' => gmp_init($collectionId),
            'params' => $params->toEncodable(),
        ]);

        return HexConverter::prefix($encoded);
    }

    public function freeze(string $collectionId, FreezeTypeParams $params): string
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('Freeze')->encode([
            'callIndex' => $this->getCallIndex('MultiTokens.freeze'),
            'collectionId' => gmp_init($collectionId),
            'freezeType' => $params->toEncodable(),
        ]);

        return HexConverter::prefix($encoded);
    }

    public function thaw(string $collectionId, FreezeTypeParams $params): string
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('Thaw')->encode([
            'callIndex' => $this->getCallIndex('MultiTokens.thaw'),
            'collectionId' => gmp_init($collectionId),
            'freezeType' => $params->toEncodable(),
        ]);

        return HexConverter::prefix($encoded);
    }

    public function setRoyalty(string $collectionId, ?string $tokenId, RoyaltyPolicyParams $royalty): string
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('SetRoyalty')->encode([
            'callIndex' => $this->getCallIndex('MultiTokens.set_royalty'),
            'collectionId' => gmp_init($collectionId),
            'tokenId' => $tokenId,
            'descriptor' => $royalty->toEncodable(),
        ]);

        return HexConverter::prefix($encoded);
    }

    public function setAttribute(string $collectionId, ?string $tokenId, string $key, string $value): string
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('SetAttribute')->encode([
            'callIndex' => $this->getCallIndex('MultiTokens.set_attribute'),
            'collectionId' => gmp_init($collectionId),
            'tokenId' => $tokenId !== null ? gmp_init($tokenId) : null,
            'key' => HexConverter::stringToHexPrefixed($key),
            'value' => HexConverter::stringToHexPrefixed($value),
        ]);

        return HexConverter::prefix($encoded);
    }

    public function removeAttribute(string $collectionId, ?string $tokenId, string $key): string
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('RemoveAttribute')->encode([
            'callIndex' => $this->getCallIndex('MultiTokens.remove_attribute'),
            'collectionId' => gmp_init($collectionId),
            'tokenId' => $tokenId !== null ? gmp_init($tokenId) : null,
            'key' => HexConverter::stringToHex($key),
        ]);

        return HexConverter::prefix($encoded);
    }

    public function removeAllAttributes(string $collectionId, ?string $tokenId, int $attributeCount): string
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('RemoveAllAttributes')->encode([
            'callIndex' => $this->getCallIndex('MultiTokens.remove_all_attributes'),
            'collectionId' => gmp_init($collectionId),
            'tokenId' => $tokenId !== null ? gmp_init($tokenId) : null,
            'attributeCount' => $attributeCount,
        ]);

        return HexConverter::prefix($encoded);
    }

    public function attributeStorage(int $module, int $method): string
    {
        $encoded = $this->scaleInstance->createTypeByTypeString('AttributeStorage')->encode([
            'module' => $module,
            'method' => $method,
        ]);

        return HexConverter::prefix($encoded);
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

    protected function getCallIndex(string $call): array
    {
        $index = str_split($this->callIndexes[$call], 2);

        return [HexConverter::hexToInt($index[0]), HexConverter::hexToInt($index[1])];
    }
}
