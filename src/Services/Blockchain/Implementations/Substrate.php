<?php

namespace Enjin\Platform\Services\Blockchain\Implementations;

use Crypto\sr25519;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Clients\Abstracts\WebsocketAbstract;
use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Enums\Substrate\CryptoSignatureType;
use Enjin\Platform\Enums\Substrate\FreezeStateType;
use Enjin\Platform\Enums\Substrate\FreezeType;
use Enjin\Platform\Enums\Substrate\TokenMintCapType;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\Models\Substrate\CreateTokenParams;
use Enjin\Platform\Models\Substrate\FreezeTypeParams;
use Enjin\Platform\Models\Substrate\MintParams;
use Enjin\Platform\Models\Substrate\MintPolicyParams;
use Enjin\Platform\Models\Substrate\OperatorTransferParams;
use Enjin\Platform\Models\Substrate\RoyaltyPolicyParams;
use Enjin\Platform\Models\Substrate\SimpleTransferParams;
use Enjin\Platform\Models\Substrate\TokenMarketBehaviorParams;
use Enjin\Platform\Services\Blockchain\Interfaces\BlockchainServiceInterface;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Support\SS58Address;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class Substrate implements BlockchainServiceInterface
{
    use HasEncodableTokenId;

    /**
     * The code service.
     */
    protected Codec $codec;

    /**
     * Create a new Substrate instance.
     */
    public function __construct(
        protected WebsocketAbstract $client,
    ) {
        $this->codec = new Codec();
    }

    /**
     * Get the client.
     */
    public function getClient(): WebsocketAbstract
    {
        return $this->client;
    }

    /**
     * Call the method in the client service.
     */
    public function callMethod(string $name, array $args = []): mixed
    {
        return $this->client->send($name, $args);
    }

    public function getSigningPayload(string $call, array $args): string
    {
        return $this->codec->encode()->signingPayload(
            call: $call,
            nonce: Arr::get($args, 'nonce'),
            blockHash: networkConfig('genesis-hash'),
            genesisHash: networkConfig('genesis-hash'),
            specVersion: networkConfig('spec-version'),
            txVersion: networkConfig('transaction-version'),
            tip: Arr::get($args, 'tip'),
        );
    }

    public function getFee(string $call): string
    {
        return Cache::remember(PlatformCache::FEE->key($call), now()->addWeek(), function () use ($call) {
            $extrinsic = $this->codec->encoder()->addFakeSignature($call);
            $result = $this->callMethod('payment_queryFeeDetails', [
                $extrinsic,
            ]);

            $baseFee = gmp_init(Arr::get($result, 'inclusionFee.baseFee'));
            $lenFee = gmp_init(Arr::get($result, 'inclusionFee.lenFee'));
            $adjustedWeightFee = gmp_init(Arr::get($result, 'inclusionFee.adjustedWeightFee'));

            return gmp_strval(gmp_add($baseFee, gmp_add($lenFee, $adjustedWeightFee)));
        });
    }

    /**
     * Get the collection policies.
     */
    public function getCollectionPolicies(array $args): array
    {
        if (Arr::get($args, 'explicitRoyaltyCurrencies')) {
            $args['explicitRoyaltyCurrencies'] = collect($args['explicitRoyaltyCurrencies'])
                ->map(function ($row) {
                    $row['tokenId'] = $this->encodeTokenId($row);
                    unset($row['encodeTokenId']);

                    return $row;
                })->toArray();
        }

        $mintPolicy = new MintPolicyParams(...$args['mintPolicy']);
        if (null !== $args['marketPolicy']) {
            $args['marketPolicy']['royalty']['beneficiary'] = SS58Address::getPublicKey($args['marketPolicy']['royalty']['beneficiary']);
        }

        $marketPolicy = null !== $args['marketPolicy'] ? new RoyaltyPolicyParams(...$args['marketPolicy']['royalty']) : null;

        return [
            'mintPolicy' => $mintPolicy,
            'marketPolicy' => $marketPolicy,
            'explicitRoyaltyCurrencies' => $args['explicitRoyaltyCurrencies'],
            'attributes' => Arr::get($args, 'attributes', []),
        ];
    }

    /**
     * Get mint or create params object.
     */
    public function getMintOrCreateParams(array $args): CreateTokenParams|MintParams
    {
        if (isset($args['initialSupply'])) {
            return $this->getCreateTokenParams($args);
        }

        return $this->getMintTokenParams($args);
    }

    /**
     * Create a new mint token params object.
     */
    public function getMintTokenParams(array $args): MintParams
    {
        $data = [
            $this->encodeTokenId($args),
            $args['amount'],
        ];
        if (isset($args['unitPrice'])) {
            $data['unitPrice'] = $args['unitPrice'];
        }

        return new MintParams(...$data);
    }

    /**
     * Create a new create token params object.
     */
    public function getCreateTokenParams(array $args): CreateTokenParams
    {
        $data = [
            $this->encodeTokenId($args),
            $args['initialSupply'],
        ];

        if (null !== $args['cap']) {
            $data['cap'] = TokenMintCapType::getEnumCase($args['cap']['type']);
            $data['supply'] = $args['cap']['amount'];
        }

        if (($beneficiary = Arr::get($args, 'behavior.hasRoyalty.beneficiary')) !== null) {
            $args['behavior']['hasRoyalty']['beneficiary'] = SS58Address::getPublicKey($beneficiary);
            $data['behavior'] = new TokenMarketBehaviorParams(hasRoyalty: new RoyaltyPolicyParams(...$args['behavior']['hasRoyalty']));
        }
        if (true === Arr::get($args, 'behavior.isCurrency')) {
            $data['behavior'] = new TokenMarketBehaviorParams(isCurrency: true);
        }

        if (isset($args['freezeState'])) {
            $data['freezeState'] = FreezeStateType::getEnumCase($args['freezeState']);
        }

        $data['listingForbidden'] = $args['listingForbidden'];
        $data['unitPrice'] = Arr::get($args, 'unitPrice');
        $data['attributes'] = Arr::get($args, 'attributes', []);

        return new CreateTokenParams(...$data);
    }

    /**
     * Create a new royalty policy params object.
     */
    public function getMutateCollectionRoyalty(array $args): null|array|RoyaltyPolicyParams
    {
        if (!isset($args['royalty'])) {
            return null;
        }

        if (null == Arr::get($args, 'royalty.beneficiary') && null == Arr::get($args, 'royalty.percentage')) {
            return [];
        }

        return new RoyaltyPolicyParams(...$args['royalty']);
    }

    /**
     * Create a new create token market behavior object.
     */
    public function getMutateTokenBehavior(array $args): null|array|TokenMarketBehaviorParams
    {
        if (!isset($args['behavior'])) {
            return null;
        }

        if ($args['behavior'] === []) {
            return [];
        }

        if (($beneficiary = Arr::get($args, 'behavior.hasRoyalty.beneficiary')) !== null) {
            $args['behavior']['hasRoyalty']['beneficiary'] = SS58Address::getPublicKey($beneficiary);

            return new TokenMarketBehaviorParams(hasRoyalty: new RoyaltyPolicyParams(...$args['behavior']['hasRoyalty']));
        }

        return new TokenMarketBehaviorParams(isCurrency: true);
    }

    /**
     * Create a new simple transfer or operator transfer params object.
     */
    public function getTransferParams(array $args): SimpleTransferParams|OperatorTransferParams
    {
        if (isset($args['source'])) {
            return $this->getOperatorTransferParams($args);
        }

        return $this->getSimpleTransferParams($args);
    }

    /**
     * Create a new operator transfer params object.
     */
    public function getOperatorTransferParams(array $args): OperatorTransferParams
    {
        $data = [
            $this->encodeTokenId($args),
            $args['source'],
            $args['amount'],
            $args['keepAlive'],
        ];

        return new OperatorTransferParams(...$data);
    }

    /**
     * Create a new simple transfer params object.
     */
    public function getSimpleTransferParams(array $args): SimpleTransferParams
    {
        $data = [
            $this->encodeTokenId($args),
            $args['amount'],
            $args['keepAlive'],
        ];

        return new SimpleTransferParams(...$data);
    }

    /**
     * Create a new freeze or thaw params object.
     */
    public function getFreezeOrThawParams(array $args): FreezeTypeParams
    {
        $data = [
            'type' => FreezeType::getEnumCase($args['freezeType']),
            'token' => $this->encodeTokenId($args),
            'account' => null,
        ];

        if (isset($args['collectionAccount']) || isset($args['tokenAccount'])) {
            $accountWallet = WalletService::firstOrStore(['public_key' => SS58Address::getPublicKey(Arr::get($args, 'collectionAccount') ?? Arr::get($args, 'tokenAccount'))]);
            $data['account'] = $accountWallet->public_key;
        }

        if (isset($args['freezeState'])) {
            $data['freezeState'] = FreezeStateType::getEnumCase($args['freezeState']);
        }

        return new FreezeTypeParams(...$data);
    }

    /**
     * Append balance details to the wallet object.
     */
    public function walletWithBalanceAndNonce(mixed $wallet): mixed
    {
        if (!$wallet) {
            return null;
        }

        if (!is_string($wallet) && null === $wallet->public_key) {
            return $wallet;
        }

        if (is_string($wallet)) {
            $wallet = WalletService::firstOrStore(['public_key' => SS58Address::getPublicKey($wallet)]);
        }

        $accountInfo = Cache::remember(
            PlatformCache::BALANCE->key($wallet->public_key),
            now()->addSeconds(12),
            fn () => $this->codec->decoder()->systemAccount($this->fetchSystemAccount($wallet->public_key))
        );

        $wallet->nonce = Arr::get($accountInfo, 'nonce');
        $wallet->balances = Arr::get($accountInfo, 'balances');

        return $wallet;
    }

    /**
     * Verify a message signature.
     */
    public function verifyMessage(string $message, string $signature, string $publicKey, string $cryptoSignatureType): bool
    {
        if ($cryptoSignatureType === CryptoSignatureType::SR25519->name) {
            $sr = new sr25519();

            return $sr->VerifySign(
                HexConverter::prefix($publicKey),
                $message,
                HexConverter::prefix($signature)
            );
        }

        try {
            return sodium_crypto_sign_verify_detached(
                sodium_hex2bin(HexConverter::unPrefix($signature)),
                sodium_hex2bin(HexConverter::unPrefix($message)),
                sodium_hex2bin(HexConverter::unPrefix($publicKey)),
            );
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Fetch the system account from the chain.
     */
    protected function fetchSystemAccount(string $publicKey): mixed
    {
        return Cache::remember(
            PlatformCache::SYSTEM_ACCOUNT->key($publicKey),
            now()->addSeconds(12),
            fn () => $this->callMethod('state_getStorage', [
                $this->codec->encoder()->systemAccountStorageKey($publicKey),
            ])
        );
    }
}
