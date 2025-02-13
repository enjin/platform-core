<?php

namespace Enjin\Platform\Services\Blockchain\Implementations;

use Crypto\sr25519;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Clients\Abstracts\WebsocketAbstract;
use Enjin\Platform\Clients\Implementations\SubstrateHttpClient;
use Enjin\Platform\Enums\Global\PlatformCache;
use Enjin\Platform\Enums\Substrate\CryptoSignatureType;
use Enjin\Platform\Enums\Substrate\FreezeStateType;
use Enjin\Platform\Enums\Substrate\FreezeType;
use Enjin\Platform\Enums\Substrate\StorageKey;
use Enjin\Platform\Enums\Substrate\TokenMintCapType;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\HasEncodableTokenId;
use Enjin\Platform\Models\Laravel\Transaction;
use Enjin\Platform\Models\Substrate\CreateTokenParams;
use Enjin\Platform\Models\Substrate\FreezeTypeParams;
use Enjin\Platform\Models\Substrate\MetadataParams;
use Enjin\Platform\Models\Substrate\MintParams;
use Enjin\Platform\Models\Substrate\MintPolicyParams;
use Enjin\Platform\Models\Substrate\OperatorTransferParams;
use Enjin\Platform\Models\Substrate\RoyaltyPolicyParams;
use Enjin\Platform\Models\Substrate\SimpleTransferParams;
use Enjin\Platform\Models\Substrate\TokenMarketBehaviorParams;
use Enjin\Platform\Services\Blockchain\Interfaces\BlockchainServiceInterface;
use Enjin\Platform\Services\Processor\Substrate\Codec\Codec;
use Enjin\Platform\Services\Processor\Substrate\Codec\Encoder;
use Enjin\Platform\Support\Account;
use Enjin\Platform\Support\SS58Address;
use Exception;
use Facades\Enjin\Platform\Services\Database\WalletService;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Enjin\Platform\Models\Substrate\AccountRulesParams;
use Enjin\Platform\Models\Substrate\DispatchRulesParams;
use Enjin\Platform\Models\Substrate\MaxFuelBurnPerTransactionParams;
use Enjin\Platform\Models\Substrate\PermittedCallsParams;
use Enjin\Platform\Models\Substrate\PermittedExtrinsicsParams;
use Enjin\Platform\Models\Substrate\RequireSignatureParams;
use Enjin\Platform\Models\Substrate\RequireTokenParams;
use Enjin\Platform\Models\Substrate\TankFuelBudgetParams;
use Enjin\Platform\Models\Substrate\UserAccountManagementParams;
use Enjin\Platform\Models\Substrate\UserFuelBudgetParams;
use Enjin\Platform\Models\Substrate\WhitelistedCallersParams;
use Enjin\Platform\Models\Substrate\WhitelistedCollectionsParams;
use Enjin\Platform\Models\Substrate\WhitelistedPalletsParams;

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

    public static function getStorageKeys()
    {
        return [
            StorageKey::collections(),
            StorageKey::pendingCollectionTransfers(),
            StorageKey::collectionAccounts(),
            StorageKey::tokens(),
            StorageKey::tokenAccounts(),
            StorageKey::attributes(),
        ];
    }

    public static function getStorageKeysForCollectionId(string $collectionId): array
    {
        return [
            StorageKey::collections(Encoder::collectionStorageKey($collectionId)),
            StorageKey::pendingCollectionTransfers(Encoder::pendingCollectionTransfersStorageKey($collectionId)),
            StorageKey::collectionAccounts(Encoder::collectionAccountStorageKey($collectionId)),
            StorageKey::tokens(Encoder::tokenStorageKey($collectionId)),
            StorageKey::tokenAccounts(Encoder::tokenAccountStorageKey($collectionId)),
            StorageKey::attributes(Encoder::attributeStorageKey($collectionId)),
        ];
    }

    public static function getStorageKeysForCollectionIds(array|Collection $collectionIds): array
    {
        return collect($collectionIds)
            ->map(fn ($collectionId) => self::getStorageKeysForCollectionId($collectionId))
            ->flatten(1)
            ->toArray();
    }

    /**
     * Call the method in the client service.
     */
    public function callMethod(string $name, array $args = [], ?bool $raw = false): mixed
    {
        return $this->client->send($name, $args, $raw);
    }

    public function createExtrinsic(
        string $signer,
        string $signature,
        string $call,
        ?string $nonce = '00',
        ?string $era = '00',
        ?string $tip = '00',
        ?string $mode = '00',
    ): string {
        return $this->codec->encoder()->addSignature(
            signer: $signer,
            signature: $signature,
            call: $call,
            nonce: $nonce,
            era: $era,
            tip: $tip,
            mode: $mode
        );
    }

    /**
     * @throws PlatformException
     */
    public function getSigningPayload(string $call, array $args): string
    {
        return $this->codec->encoder()->signingPayload(
            call: $call,
            nonce: Arr::get($args, 'nonce'),
            blockHash: networkConfig('genesis-hash'),
            genesisHash: networkConfig('genesis-hash'),
            specVersion: cachedRuntimeConfig(PlatformCache::SPEC_VERSION, currentMatrix()),
            txVersion: cachedRuntimeConfig(PlatformCache::TRANSACTION_VERSION, currentMatrix()),
            tip: Arr::get($args, 'tip'),
        );
    }

    /**
     * @throws PlatformException
     */
    public function getSigningPayloadJSON(Transaction $transaction, array $args): array
    {
        return $this->codec->encoder()->signingPayloadJSON(
            call: $transaction['encoded_data'],
            address: $transaction['wallet_public_key'] ?? Account::daemonPublicKey(),
            nonce: Arr::get($args, 'nonce'),
            blockHash: networkConfig('genesis-hash'),
            genesisHash: networkConfig('genesis-hash'),
            specVersion: cachedRuntimeConfig(PlatformCache::SPEC_VERSION, currentMatrix()),
            txVersion: cachedRuntimeConfig(PlatformCache::TRANSACTION_VERSION, currentMatrix()),
            tip: Arr::get($args, 'tip'),
        );
    }

    public function getFee(string $call): string
    {
        return Cache::remember(PlatformCache::FEE->key($call), now()->addWeek(), function () use ($call) {
            $extrinsic = $this->codec->encoder()->addFakeSignature($call);
            $result = (new SubstrateHttpClient())
                ->jsonRpc('payment_queryFeeDetails', [
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

        if ($args['marketPolicy'] !== null) {
            $args['marketPolicy']['royalty']['beneficiary'] = SS58Address::getPublicKey($args['marketPolicy']['royalty']['beneficiary']);
        }
        $marketPolicy = $args['marketPolicy'] !== null ? new RoyaltyPolicyParams(...$args['marketPolicy']['royalty']) : null;

        if (Arr::get($args, 'mintPolicy.forceCollapsingSupply') === false && Arr::get($args, 'mintPolicy.forceSingleMint') === true) {
            $args['mintPolicy']['forceCollapsingSupply'] = true;
        }
        unset($args['mintPolicy']['forceSingleMint']);

        $mintPolicy = $args['mintPolicy'] !== null ? new MintPolicyParams(...$args['mintPolicy']) : null;

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

        return new MintParams(...$data);
    }

    /**
     * Create a CreateTokenParams object.
     */
    public function getCreateTokenParams(array $args): CreateTokenParams
    {
        $data = [
            'tokenId' => $this->encodeTokenId($args),
            'accountDepositCount' => $args['accountDepositCount'],
            'initialSupply' => $args['initialSupply'],
            'listingForbidden' => $args['listingForbidden'],
            'attributes' => Arr::get($args, 'attributes', []),
            'infusion' => $args['infusion'],
            'anyoneCanInfuse' => $args['anyoneCanInfuse'],
        ];

        $cap = Arr::get($args, 'cap.type');

        // TODO: SingleMint can be removed on v2.1.0
        if ($cap === 'SINGLE_MINT') {
            $data['cap'] = TokenMintCapType::COLLAPSING_SUPPLY;
            $data['capSupply'] = $args['initialSupply'];
        }
        // TODO: Infinite can be removed on v2.1.0
        elseif ($cap === 'INFINITE') {
            $data['cap'] = null;
        } elseif ($cap !== null) {
            $data['cap'] = TokenMintCapType::getEnumCase($cap);
            $data['capSupply'] = $args['cap']['amount'];
        }

        if (($beneficiary = Arr::get($args, 'behavior.hasRoyalty.beneficiary')) !== null) {
            $args['behavior']['hasRoyalty']['beneficiary'] = SS58Address::getPublicKey($beneficiary);
            $data['behavior'] = new TokenMarketBehaviorParams(hasRoyalty: new RoyaltyPolicyParams(...$args['behavior']['hasRoyalty']));
        }
        if (Arr::get($args, 'behavior.isCurrency') === true) {
            $data['behavior'] = new TokenMarketBehaviorParams(isCurrency: true);
        }

        if (isset($args['freezeState'])) {
            $data['freezeState'] = FreezeStateType::getEnumCase($args['freezeState']);
        }

        if (isset($args['metadata'])) {
            $data['metadata'] = new MetadataParams(...$args['metadata']);
        }

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

        if (Arr::get($args, 'royalty.beneficiary') == null && Arr::get($args, 'royalty.percentage') == null) {
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

        if (!is_string($wallet) && $wallet->public_key === null) {
            return $wallet;
        }

        if (is_string($wallet)) {
            $wallet = WalletService::firstOrStore(['public_key' => SS58Address::getPublicKey($wallet)]);
        }

        try {
            $storage = $this->fetchSystemAccount($wallet->public_key);
            $accountInfo = $this->codec->decoder()->systemAccount($storage);
        } catch (Exception) {
            return $wallet;
        }

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
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Set user account management param object.
     */
    public function getUserAccountManagementParams(?array $args = null): ?UserAccountManagementParams
    {
        if (!is_null($creationDeposit = Arr::get($args, 'reservesAccountCreationDeposit'))) {
            return new UserAccountManagementParams(
                $creationDeposit ?? false
            );
        }

        return null;
    }

    /**
     * Set the dispatch rule params object.
     */
    public function getDispatchRulesParams(array $args): DispatchRulesParams
    {
        return new DispatchRulesParams(
            0, // TODO: Add ruleSet
            ($callers = Arr::get($args, 'whitelistedCallers'))
                ? new WhitelistedCallersParams($callers)
                : null,
            ($requireToken = Arr::get($args, 'requireToken'))
                ? new RequireTokenParams(Arr::get($requireToken, 'collectionId'), $this->encodeTokenId($requireToken))
                : null,
            ($collections = Arr::get($args, 'whitelistedCollections'))
                ? new WhitelistedCollectionsParams($collections)
                : null,
            (!is_null($maxFuelBurnPerTransaction = Arr::get($args, 'maxFuelBurnPerTransaction')))
                ? new MaxFuelBurnPerTransactionParams($maxFuelBurnPerTransaction)
                : null,
            (!is_null($userFuelBudget = Arr::get($args, 'userFuelBudget')))
                ? new UserFuelBudgetParams(Arr::get($userFuelBudget, 'amount'), Arr::get($userFuelBudget, 'resetPeriod'))
                : null,
            (!is_null($tankFuelBudget = Arr::get($args, 'tankFuelBudget')))
                ? new TankFuelBudgetParams(Arr::get($tankFuelBudget, 'amount'), Arr::get($tankFuelBudget, 'resetPeriod'))
                : null,
            ($permittedCalls = Arr::get($args, 'permittedCalls'))
                ? new PermittedCallsParams(Arr::get($permittedCalls, 'calls'))
                : null,
            ($permittedExtrinsics = Arr::get($args, 'permittedExtrinsics'))
                ? (new PermittedExtrinsicsParams())->fromMethods($permittedExtrinsics)
                : null,
            ($pallets = Arr::get($args, 'whitelistedPallets'))
                ? new WhitelistedPalletsParams($pallets)
                : null,
            ($requireSignature = Arr::get($args, 'requireSignature'))
                ? new RequireSignatureParams($requireSignature)
                : null,
        );
    }

    /**
     * Set dispatch rules.
     */
    public function getDispatchRulesParamsArray(array $args): array
    {
        $dispatchRulesParams = [];
        if ($rules = Arr::get($args, 'dispatchRules')) {
            foreach ($rules as $rule) {
                $dispatchRulesParams[] = $this->getDispatchRulesParams($rule);
            }
        }

        return $dispatchRulesParams;
    }

    /**
     * Set account rule params object.
     */
    public function getAccountRulesParams(?array $args = null): ?AccountRulesParams
    {
        if (is_null($args)) {
            return null;
        }

        $accountRulesParams = null;
        $callers = Arr::get($args, 'accountRules.whitelistedCallers');
        $requireToken = Arr::get($args, 'accountRules.requireToken');
        if ($callers || $requireToken) {
            $accountRulesParams = new AccountRulesParams(
                $callers
                    ? new WhitelistedCallersParams($callers)
                    : null,
                $requireToken
                    ? new RequireTokenParams(Arr::get($requireToken, 'collectionId'), $this->encodeTokenId($requireToken))
                    : null
            );
        }

        return $accountRulesParams;
    }

    /**
     * Fetch the system account from the chain.
     */
    protected function fetchSystemAccount(string $publicKey): mixed
    {
        return Cache::remember(
            PlatformCache::SYSTEM_ACCOUNT->key($publicKey),
            now()->addSeconds(12),
            fn () => (new SubstrateHttpClient())
                ->jsonRpc('state_getStorage', [
                    $this->codec->encoder()->systemAccountStorageKey($publicKey),
                ])
        );
    }
}
