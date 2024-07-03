<?php

namespace Enjin\Platform\Services\Processor\Substrate;

use Carbon\Carbon;
use Closure;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Models\Attribute;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\CollectionAccount;
use Enjin\Platform\Models\CollectionAccountApproval;
use Enjin\Platform\Models\Laravel\CollectionRoyaltyCurrency;
use Enjin\Platform\Models\Token;
use Enjin\Platform\Models\TokenAccount;
use Enjin\Platform\Models\TokenAccountApproval;
use Enjin\Platform\Services\Database\CollectionService;
use Enjin\Platform\Services\Database\TokenService;
use Enjin\Platform\Services\Database\WalletService;
use Enjin\Platform\Services\Serialization\Implementations\Substrate;
use Illuminate\Support\Arr;

class Parser
{
    protected static $walletCache = [];
    protected static $collectionCache = [];
    protected static $collectionAccountCache = [];
    protected static $tokenCache = [];
    protected static $tokenAccountCache = [];

    protected Substrate $serializationService;

    protected CollectionService $collectionService;

    protected TokenService $tokenService;

    protected WalletService $walletService;

    /**
     * Create instance.
     */
    public function __construct(?Substrate $serializationService = null)
    {
        $this->serializationService = $serializationService ?? new Substrate();
        $this->walletService = new WalletService();
        $this->collectionService = new CollectionService($this->walletService);
        $this->tokenService = new TokenService($this->walletService);
    }

    /**
     * Store collections.
     */
    public function collectionsStorages(array $data, bool $hotSync = false): void
    {
        $insertData = [];
        $insertRoyaltyCurrencies = [];

        foreach ($data as [$key, $collection]) {
            $collectionKey = $this->serializationService->decode('collectionStorageKey', $key);
            $collectionData = $this->serializationService->decode('collectionStorageData', $collection);
            $ownerWallet = $this->getCachedWallet(
                $collectionData['owner'],
                fn () => $this->walletService->firstOrStore(['account' => $collectionData['owner']])
            );
            $royaltyWallet = $this->getCachedWallet(
                $collectionData['royaltyBeneficiary'],
                fn () => $this->walletService->firstOrStore(['account' => $collectionData['royaltyBeneficiary']])
            );

            if (!empty($royaltyCurrencies = $collectionData['explicitRoyaltyCurrencies'])) {
                $insertRoyaltyCurrencies[] = [
                    'collectionId' => $collectionKey['collectionId'],
                    'currencies' => $royaltyCurrencies,
                ];
            }

            $insertData[] = [
                'collection_chain_id' => $collectionKey['collectionId'],
                'owner_wallet_id' => $ownerWallet->id,
                'max_token_count' => $collectionData['maxTokenCount'],
                'max_token_supply' => $collectionData['maxTokenSupply'],
                'force_single_mint' => $collectionData['forceSingleMint'],
                'is_frozen' => $collectionData['isFrozen'],
                'royalty_wallet_id' => $royaltyWallet?->id,
                'royalty_percentage' => $collectionData['royaltyPercentage'],
                'token_count' => $collectionData['tokenCount'],
                'attribute_count' => $collectionData['attributeCount'],
                'total_deposit' => $collectionData['totalDeposit'],
                'network' => config('enjin-platform.chains.network'),
                'created_at' => $now = Carbon::now(),
                'updated_at' => $now,
            ];
        }

        if ($hotSync) {
            Collection::upsert($insertData, uniqueBy: ['collection_chain_id']);
        } else {
            $this->collectionService->insert($insertData);
        }

        $this->collectionsRoyaltyCurrencies($insertRoyaltyCurrencies);
    }

    public function pendingCollectionTransfersStorages(array $data): void
    {
        foreach ($data as [$key, $account]) {
            $pendingTransferKey = $this->serializationService->decode('pendingCollectionTransferStorageKey', $key);
            $pendingTransferData = $this->serializationService->decode('pendingCollectionTransferStorageData', $account);

            Collection::where('collection_chain_id', $pendingTransferKey['collectionId'])?->update(['pending_transfer' => $pendingTransferData['accountId']]);
        }
    }

    /**
     * Store collection.
     */
    public function collectionStorage(string $key, string $data): mixed
    {
        $collectionKey = $this->serializationService->decode('collectionStorageKey', $key);
        $collectionData = $this->serializationService->decode('collectionStorageData', $data);
        $ownerWallet = $this->getCachedWallet(
            $collectionData['owner'],
            fn () => $this->walletService->firstOrStore(['account' => $collectionData['owner']])
        );
        $royaltyWallet = $this->getCachedWallet(
            $collectionData['royaltyBeneficiary'],
            fn () => $this->walletService->firstOrStore(['account' => $collectionData['royaltyBeneficiary']])
        );


        $collection = $this->collectionService->store([
            'collection_chain_id' => $collectionKey['collectionId'],
            'owner_wallet_id' => $ownerWallet->id,
            'max_token_count' => $collectionData['maxTokenCount'],
            'max_token_supply' => $collectionData['maxTokenSupply'],
            'force_single_mint' => $collectionData['forceSingleMint'],
            'is_frozen' => $collectionData['isFrozen'],
            'royalty_wallet_id' => $royaltyWallet?->id,
            'royalty_percentage' => $collectionData['royaltyPercentage'],
            'token_count' => $collectionData['tokenCount'],
            'attribute_count' => $collectionData['attributeCount'],
            'total_deposit' => $collectionData['totalDeposit'],
            'network' => config('enjin-platform.chains.network'),
            'created_at' => $now = Carbon::now(),
            'updated_at' => $now,
        ]);

        $this->collectionRoyaltyCurrencies($collection->id, $collectionData['explicitRoyaltyCurrencies']);

        return $collection;
    }

    /**
     * Store tokens.
     */
    public function tokensStorages(array $data, bool $hotSync = false): void
    {
        $insertData = [];

        foreach ($data as [$key, $token]) {
            $tokenKey = $this->serializationService->decode('tokenStorageKey', $key);
            $tokenData = $this->serializationService->decode('tokenStorageData', $token);

            $collection = $this->getCachedCollection(
                $tokenKey['collectionId'],
                fn () => Collection::where('collection_chain_id', $tokenKey['collectionId'])->firstOrFail()
            );
            $royaltyWallet = $this->getCachedWallet(
                $tokenData['royaltyBeneficiary'],
                fn () => $this->walletService->firstOrStore(['account' => $tokenData['royaltyBeneficiary']])
            );

            $insertData[] = [
                'token_chain_id' => $tokenKey['tokenId'],
                'collection_id' => $collection->id,
                'supply' => $tokenData['supply'],
                'cap' => $tokenData['cap']->name,
                'cap_supply' => $tokenData['capSupply'],
                'is_frozen' => $tokenData['isFrozen'],
                'royalty_wallet_id' => $royaltyWallet?->id,
                'royalty_percentage' => $tokenData['royaltyPercentage'],
                'is_currency' => $tokenData['isCurrency'],
                'listing_forbidden' => $tokenData['listingForbidden'],
                'minimum_balance' => $tokenData['minimumBalance'],
                'unit_price' => $tokenData['unitPrice'],
                'attribute_count' => $tokenData['attributeCount'],
                'created_at' => $now = Carbon::now(),
                'updated_at' => $now,
            ];
        }

        if ($hotSync) {
            Token::upsert($insertData, uniqueBy: ['token_chain_id', 'collection_id']);
        } else {
            $this->tokenService->insert($insertData);
        }
    }

    /**
     * Store token.
     */
    public function tokenStorage(string $key, string $data): mixed
    {
        $tokenKey = $this->serializationService->decode('tokenStorageKey', $key);
        $tokenData = $this->serializationService->decode('tokenStorageData', $data);

        $collectionStored = $this->getCachedCollection(
            $tokenKey['collectionId'],
            fn () => Collection::where('collection_chain_id', $tokenKey['collectionId'])->firstOrFail()
        );
        $royaltyWallet =  $this->getCachedWallet(
            $tokenData['royaltyBeneficiary'],
            fn () => $this->walletService->firstOrStore(['account' => $tokenData['royaltyBeneficiary']])
        );

        return $this->tokenService->updateOrStore(
            [
                'collection_id' => $collectionStored->id,
                'token_chain_id' => $tokenKey['tokenId'],
            ],
            [
                'supply' => $tokenData['supply'],
                'cap' => $tokenData['cap']->name,
                'cap_supply' => $tokenData['capSupply'],
                'is_frozen' => $tokenData['isFrozen'],
                'royalty_wallet_id' => $royaltyWallet?->id,
                'royalty_percentage' => $tokenData['royaltyPercentage'],
                'is_currency' => $tokenData['isCurrency'],
                'listing_forbidden' => $tokenData['listingForbidden'],
                'minimum_balance' => $tokenData['minimumBalance'],
                'unit_price' => $tokenData['unitPrice'],
                'attribute_count' => $tokenData['attributeCount'],
            ]
        );
    }

    /**
     * Store collection accounts.
     */
    public function collectionsAccountsStorages(array $data, bool $hotSync = false): void
    {
        $insertData = [];
        $insertApprovals = [];

        foreach ($data as [$key, $collectionAccount]) {
            $collectionAccountKey = $this->serializationService->decode('collectionAccountStorageKey', $key);
            $collectionAccountData = $this->serializationService->decode('collectionAccountStorageData', $collectionAccount);
            $wallet = $this->getCachedWallet(
                $collectionAccountKey['accountId'],
                fn () => $this->walletService->firstOrStore(['account' => $collectionAccountKey['accountId']])
            );

            $collection = $this->getCachedCollection(
                $collectionAccountKey['collectionId'],
                fn () => Collection::where('collection_chain_id', $collectionAccountKey['collectionId'])->firstOrFail()
            );

            if (!empty($approvals = $collectionAccountData['approvals'])) {
                $insertApprovals[] = [
                    'walletId' => $wallet->id,
                    'collectionId' => $collection->id,
                    'approvals' => $approvals,
                ];
            }

            $insertData[] = [
                'collection_id' => $collection->id,
                'wallet_id' => $wallet->id,
                'is_frozen' => $collectionAccountData['isFrozen'],
                'account_count' => $collectionAccountData['accountCount'],
                'created_at' => $now = Carbon::now(),
                'updated_at' => $now,
            ];
        }

        if ($hotSync) {
            CollectionAccount::upsert($insertData, uniqueBy: ['collection_id', 'wallet_id']);
        } else {
            CollectionAccount::insert($insertData);
        }

        $this->collectionsAccountsApprovals($insertApprovals);
    }

    /**
     * Store collection account.
     */
    public function collectionAccountStorage(string $key, string $data): mixed
    {
        $collectionAccountKey = $this->serializationService->decode('collectionAccountStorageKey', $key);
        $collectionAccountData = $this->serializationService->decode('collectionAccountStorageData', $data);

        $walletStored = $this->getCachedWallet(
            $collectionAccountKey['accountId'],
            fn () => $this->walletService->firstOrStore(['account' => $collectionAccountKey['accountId']])
        );
        $collectionStored = $this->getCachedCollection(
            $collectionAccountKey['collectionId'],
            fn () => Collection::where('collection_chain_id', $collectionAccountKey['collectionId'])->firstOrFail()
        );

        $collectionAccount = CollectionAccount::updateOrCreate(
            [
                'collection_id' => $collectionStored->id,
                'wallet_id' => $walletStored->id,
            ],
            [
                'is_frozen' => $collectionAccountData['isFrozen'],
                'account_count' => $collectionAccountData['accountCount'],
            ]
        );

        $this->collectionAccountApprovals($collectionAccount->id, $collectionAccountData['approvals']);

        return $collectionAccount;
    }

    /**
     * Store token accounts.
     */
    public function tokensAccountsStorages(array $data, bool $hotSync = false): void
    {
        $insertData = [];
        $insertApprovals = [];

        foreach ($data as [$key, $tokenAccount]) {
            $tokenAccountKey = $this->serializationService->decode('tokenAccountStorageKey', $key);
            $tokenAccountData = $this->serializationService->decode('tokenAccountStorageData', $tokenAccount);
            $wallet = $this->getCachedWallet(
                $tokenAccountKey['accountId'],
                fn () => $this->walletService->firstOrStore(['account' => $tokenAccountKey['accountId']])
            );
            $collection = $this->getCachedCollection(
                $tokenAccountKey['collectionId'],
                fn () => Collection::where('collection_chain_id', $tokenAccountKey['collectionId'])->firstOrFail()
            );

            $token = $this->getCachedToken(
                $collection->id . '-' . $tokenAccountKey['tokenId'],
                fn () => Token::where(['collection_id' => $collection->id, 'token_chain_id' => $tokenAccountKey['tokenId']])->firstOrFail()
            );

            if (!empty($approvals = $tokenAccountData['approvals'])) {
                $insertApprovals[] = [
                    'walletId' => $wallet->id,
                    'collectionId' => $collection->id,
                    'tokenId' => $token->id,
                    'approvals' => $approvals,
                ];
            }

            $insertData[] = [
                'wallet_id' => $wallet->id,
                'collection_id' => $collection->id,
                'token_id' => $token->id,
                'balance' => $tokenAccountData['balance'],
                'reserved_balance' => $tokenAccountData['reservedBalance'],
                'is_frozen' => $tokenAccountData['isFrozen'],
                'created_at' => $now = Carbon::now(),
                'updated_at' => $now,
            ];
        }

        if ($hotSync) {
            TokenAccount::upsert($insertData, uniqueBy: ['wallet_id', 'collection_id', 'token_id']);
        } else {
            TokenAccount::insert($insertData);
        }

        $this->tokensAccountsApprovals($insertApprovals);
    }

    /**
     * Store token account.
     */
    public function tokenAccountStorage(string $key, string $data): mixed
    {
        $tokenAccountKey = $this->serializationService->decode('tokenAccountStorageKey', $key);
        $tokenAccountData = $this->serializationService->decode('tokenAccountStorageData', $data);

        $walletStored = $this->getCachedWallet(
            $tokenAccountKey['accountId'],
            fn () => $this->walletService->firstOrStore(['account' => $tokenAccountKey['accountId']])
        );
        $collectionStored = $this->getCachedCollection(
            $tokenAccountKey['collectionId'],
            fn () => Collection::where('collection_chain_id', $tokenAccountKey['collectionId'])->firstOrFail()
        );
        $tokenStored = $this->getCachedToken(
            $collectionStored->id . '-' . $tokenAccountKey['tokenId'],
            fn () => Token::where(['collection_id' => $collectionStored->id, 'token_chain_id' => $tokenAccountKey['tokenId']])->firstOrFail()
        );

        $tokenAccount = TokenAccount::updateOrCreate(
            [
                'wallet_id' => $walletStored->id,
                'collection_id' => $collectionStored->id,
                'token_id' => $tokenStored->id,
            ],
            [
                'balance' => $tokenAccountData['balance'],
                'reserved_balance' => $tokenAccountData['reservedBalance'],
                'is_frozen' => $tokenAccountData['isFrozen'],
            ]
        );

        $this->tokenAccountApprovals($tokenAccount->id, $tokenAccountData['approvals']);

        return $tokenAccount;
    }

    /**
     * Store attributes.
     */
    public function attributesStorages(array $data, bool $hotSync = false): void
    {
        $insertData = [];

        foreach ($data as [$key, $attribute]) {
            $attributeKey = $this->serializationService->decode('attributeStorageKey', $key);
            // TODO: We dont use but we could decode the storage of an attribute to get
            //          value: Bytes
            //          deposit: Compact<u128>
            //          depositor: Option<AccountId>
            $attributeData = $this->serializationService->decode('bytes', $attribute);

            $collection = $this->getCachedCollection(
                $attributeKey['collectionId'],
                fn () => Collection::where('collection_chain_id', $attributeKey['collectionId'])->firstOrFail()
            );

            $token = is_null($attributeKey['tokenId']) ? null : $this->getCachedToken(
                $collection->id . '-' . $attributeKey['tokenId'],
                fn () => Token::where(['collection_id' => $collection->id, 'token_chain_id' => $attributeKey['tokenId']])->first()
            );

            $insertData[] = [
                'collection_id' => $collection->id,
                'token_id' => $token?->id,
                'key' => HexConverter::prefix($attributeKey['attribute']),
                'value' => HexConverter::prefix($attributeData),
            ];
        }

        if ($hotSync) {
            foreach ($insertData as $data) {
                Attribute::updateOrInsert(
                    Arr::except($data, 'value'),
                    $data
                );
            }
        } else {
            Attribute::insert($insertData);
        }
    }

    /**
     * Store attribute.
     */
    public function attributeStorage(string $key, string $data): mixed
    {
        $attributeKey = $this->serializationService->decode('attributeStorageKey', $key);
        $attributeData = $this->serializationService->decode('bytes', $data);

        $collectionStored = $this->getCachedCollection(
            $attributeKey['collectionId'],
            fn () => Collection::where('collection_chain_id', $attributeKey['collectionId'])->firstOrFail()
        );
        $tokenStored = is_null($attributeKey['tokenId']) ? null : $this->getCachedToken(
            $collectionStored->id . '-' . $attributeKey['tokenId'],
            fn () => Token::where(['collection_id' => $collectionStored->id, 'token_chain_id' => $attributeKey['tokenId']])->first()
        );

        return Attribute::create([
            'collection_id' => $collectionStored->id,
            'token_id' => $tokenStored->id,
            'key' => HexConverter::prefix($attributeKey['attribute']),
            'value' => HexConverter::prefix($attributeData),
        ]);
    }

    /**
     * Get cached wallet.
     */
    protected function getCachedWallet(?string $key, ?Closure $default = null): mixed
    {
        if (is_null($key)) {
            return null;
        }

        if (!isset(static::$walletCache[$key])) {
            static::$walletCache[$key] = $default();
        }

        return static::$walletCache[$key];
    }

    /**
     * Get cached collection.
     */
    protected function getCachedCollection(string $key, ?Closure $default = null): mixed
    {
        if (!isset(static::$collectionCache[$key])) {
            static::$collectionCache[$key] = $default();
        }

        return static::$collectionCache[$key];
    }

    /**
     * Get cached collection account.
     */
    protected function getCachedCollectionAccount(string $key, ?Closure $default = null): mixed
    {
        if (!isset(static::$collectionAccountCache[$key])) {
            static::$collectionAccountCache[$key] = $default();
        }

        return static::$collectionAccountCache[$key];
    }

    /**
     * Get cached token.
     */
    protected function getCachedToken(string $key, ?Closure $default = null): mixed
    {
        if (!isset(static::$tokenCache[$key])) {
            static::$tokenCache[$key] = $default();
        }

        return static::$tokenCache[$key];
    }

    /**
     * Get cached toke account.
     */
    protected function getCachedTokenAccount(string $key, ?Closure $default = null): mixed
    {
        if (!isset(static::$tokenAccountCache[$key])) {
            static::$tokenAccountCache[$key] = $default();
        }

        return static::$tokenAccountCache[$key];
    }

    /**
     * Store collections royalty currencies.
     */
    protected function collectionsRoyaltyCurrencies(array $data, bool $hotSync = false): void
    {
        if (empty($data)) {
            return;
        }

        $insertData = [];
        foreach ($data as $royaltyCurrency) {
            $collection = $this->getCachedCollection(
                $royaltyCurrency['collectionId'],
                fn () => Collection::where(['collection_chain_id' => $royaltyCurrency['collectionId']])->firstOrFail()
            );

            foreach ($royaltyCurrency['currencies'] as $currency) {
                $insertData[] = [
                    'collection_id' => $collection->id,
                    'currency_collection_chain_id' => $currency['collectionId'],
                    'currency_token_chain_id' => $currency['tokenId'],
                    'created_at' => $now = Carbon::now(),
                    'updated_at' => $now,
                ];
            }
        }

        CollectionRoyaltyCurrency::upsert($insertData, uniqueBy: ['collection_id', 'currency_collection_chain_id', 'currency_token_chain_id']);
    }

    /**
     * Store collection royalty currencies.
     */
    protected function collectionRoyaltyCurrencies(string $collectionId, array $royaltyCurrencies): void
    {
        foreach ($royaltyCurrencies as $currency) {
            CollectionRoyaltyCurrency::updateOrCreate(
                [
                    'collection_id' => $collectionId,
                    'currency_collection_chain_id' => $currency['collectionId'],
                    'currency_token_chain_id' => $currency['tokenId'],
                ],
                [
                    'created_at' => $now = Carbon::now(),
                    'updated_at' => $now,
                ]
            );
        }
    }

    /**
     * Store token accounts approvals.
     */
    protected function tokensAccountsApprovals(array $data, bool $hotSync = false): void
    {
        if (empty($data)) {
            return;
        }

        $insertData = [];

        foreach ($data as $accountApprovals) {
            $tokenAccount = $this->getCachedTokenAccount(
                $accountApprovals['walletId'] . '|' . $accountApprovals['collectionId'] . '|' . $accountApprovals['tokenId'],
                fn () => TokenAccount::where(['wallet_id' => $accountApprovals['walletId'], 'collection_id' => $accountApprovals['collectionId'], 'token_id' => $accountApprovals['tokenId']])->firstOrFail()
            );
            foreach ($accountApprovals['approvals'] as $approval) {
                $wallet = $this->getCachedWallet(
                    $approval['accountId'],
                    fn () => $this->walletService->firstOrStore(['account' => $approval['accountId']])
                );
                $insertData[] = [
                    'token_account_id' => $tokenAccount->id,
                    'wallet_id' => $wallet->id,
                    'amount' => $approval['amount'],
                    'expiration' => $approval['expiration'],
                    'created_at' => $now = Carbon::now(),
                    'updated_at' => $now,
                ];
            }
        }

        if ($hotSync) {
            TokenAccountApproval::upsert($insertData, uniqueBy: ['token_account_id', 'wallet_id']);
        } else {
            TokenAccountApproval::insert($insertData);
        }
    }

    /**
     * Store token account approvals.
     */
    protected function tokenAccountApprovals(string $tokenAccountId, array $approvals): void
    {
        foreach ($approvals as $approval) {
            $wallet = $this->walletService->firstOrStore(['account' => $approval['accountId']]);

            TokenAccountApproval::updateOrCreate(
                [
                    'token_account_id' => $tokenAccountId,
                    'wallet_id' => $wallet->id,
                ],
                [
                    'amount' => $approval['amount'],
                    'expiration' => $approval['expiration'],
                    'created_at' => $now = Carbon::now(),
                    'updated_at' => $now,
                ]
            );
        }
    }

    /**
     * Store collection accounts approvals.
     */
    protected function collectionsAccountsApprovals(array $data, bool $hotSync = false): void
    {
        if (empty($data)) {
            return;
        }

        $insertData = [];

        foreach ($data as $accountApprovals) {
            $tokenAccount = $this->getCachedCollectionAccount(
                $accountApprovals['walletId'] . '|' . $accountApprovals['collectionId'],
                fn () => CollectionAccount::where(['wallet_id' => $accountApprovals['walletId'], 'collection_id' => $accountApprovals['collectionId']])->firstOrFail()
            );

            foreach ($accountApprovals['approvals'] as $approval) {
                $wallet = $this->getCachedWallet(
                    $approval['accountId'],
                    fn () => $this->walletService->firstOrStore(['account' => $approval['accountId']])
                );

                $insertData[] = [
                    'collection_account_id' => $tokenAccount->id,
                    'wallet_id' => $wallet->id,
                    'expiration' => $approval['expiration'],
                    'created_at' => $now = Carbon::now(),
                    'updated_at' => $now,
                ];
            }
        }

        if ($hotSync) {
            CollectionAccountApproval::upsert($insertData, uniqueBy: ['collection_account_id', 'wallet_id']);
        } else {
            CollectionAccountApproval::insert($insertData);
        }
    }

    /**
     * Store collection account approvals.
     */
    protected function collectionAccountApprovals(string $collectionAccountId, array $approvals): void
    {
        foreach ($approvals as $approval) {
            $wallet = $this->walletService->firstOrStore(['account' => $approval['accountId']]);

            CollectionAccountApproval::updateOrCreate(
                [
                    'collection_account_id' => $collectionAccountId,
                    'wallet_id' => $wallet->id,
                ],
                [
                    'expiration' => $approval['expiration'],
                    'created_at' => $now = Carbon::now(),
                    'updated_at' => $now,
                ]
            );
        }
    }
}
