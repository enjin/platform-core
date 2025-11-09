<?php

namespace Enjin\Platform\Services\Processor\Substrate\Codec;

use Codec\ScaleBytes;
use Codec\Types\ScaleInstance;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Enums\Substrate\FeeSide;
use Enjin\Platform\Enums\Substrate\RuntimeHoldReason;
use Enjin\Platform\Enums\Substrate\TokenMintCapType;
use Enjin\Platform\Models\Substrate\CreateTokenParams;
use Enjin\Platform\Models\Substrate\FreezeTypeParams;
use Enjin\Platform\Models\Substrate\MintParams;
use Enjin\Platform\Models\Substrate\MintPolicyParams;
use Enjin\Platform\Models\Substrate\MultiTokensTokenAssetIdParams;
use Enjin\Platform\Models\Substrate\RoyaltyPolicyParams;
use Enjin\Platform\Enums\Substrate\CoveragePolicy;
use Enjin\Platform\Models\Substrate\AccountRulesParams;
use Enjin\Platform\Models\Substrate\DispatchRulesParams;
use Illuminate\Support\Arr;

class Decoder
{
    public function __construct(protected ScaleInstance $codec) {}

    public function compact(string $data)
    {
        return $this->codec->process('Compact', new ScaleBytes($data));
    }

    public function systemAccount(?string $data = null): array
    {
        $decoded = $data === null ? null : $this->codec->process('AccountInfoWithTripleRefCount', new ScaleBytes($data));

        return [
            'nonce' => Arr::get($decoded, 'nonce', 0),
            'consumers' => Arr::get($decoded, 'consumers', 0),
            'providers' => Arr::get($decoded, 'providers', 0),
            'sufficients' => Arr::get($decoded, 'sufficients', 0),
            'balances' => [
                'free' => gmp_strval(Arr::get($decoded, 'data.free', '0')),
                'reserved' => gmp_strval(Arr::get($decoded, 'data.reserved', '0')),
                'miscFrozen' => gmp_strval(Arr::get($decoded, 'data.miscFrozen', '0')),
                'feeFrozen' => gmp_strval(Arr::get($decoded, 'data.feeFrozen', '0')),
            ],
        ];
    }

    public function createCollection(string $data): array
    {
        $decoded = $this->codec->process('CreateCollection', new ScaleBytes($data));

        return [
            'mintPolicy' => MintPolicyParams::fromEncodable(Arr::get($decoded, 'descriptor.policy.mint'))->toArray(),
            'marketPolicy' => ($royalty = Arr::get($decoded, 'descriptor.policy.market')) !== null
                ? RoyaltyPolicyParams::fromEncodable($royalty)->toArray()
                : null,
        ];
    }

    public function destroyCollection(string $data): array
    {
        $decoded = $this->codec->process('DestroyCollection', new ScaleBytes($data));

        return [
            'collectionId' => gmp_strval(Arr::get($decoded, 'collectionId')),
        ];
    }

    public function mint(string $data): array
    {
        $decoded = $this->codec->process('Mint', new ScaleBytes($data));
        $params = Arr::get($decoded, 'params');

        return [
            'recipientId' => ($recipient = Arr::get($decoded, 'recipient.Id')) !== null ? HexConverter::prefix($recipient) : null,
            'collectionId' => gmp_strval(Arr::get($decoded, 'collectionId')),
            'params' => Arr::exists($params, 'CreateToken') ?
                CreateTokenParams::fromEncodable(Arr::get($params, 'CreateToken'))->toArray()
                :
                MintParams::fromEncodable(Arr::get($params, 'Mint'))->toArray(),
        ];
    }

    public function burn(string $data): array
    {
        $decoded = $this->codec->process('Burn', new ScaleBytes($data));

        return [
            'collectionId' => gmp_strval(Arr::get($decoded, 'collectionId')),
            'tokenId' => gmp_strval(Arr::get($decoded, 'params.tokenId')),
            'amount' => gmp_strval(Arr::get($decoded, 'params.amount')),
            'removeTokenStorage' => Arr::get($decoded, 'params.removeTokenStorage'),
        ];
    }

    public function freeze(string $data): array
    {
        $decoded = $this->codec->process('Freeze', new ScaleBytes($data));

        return [
            'collectionId' => gmp_strval(Arr::get($decoded, 'collectionId')),
            'freezeType' => FreezeTypeParams::fromEncodable(Arr::get($decoded, 'freezeType'))->toArray(),
        ];
    }

    public function thaw(string $data): array
    {
        $decoded = $this->codec->process('Thaw', new ScaleBytes($data));

        return [
            'collectionId' => gmp_strval(Arr::get($decoded, 'collectionId')),
            'freezeType' => FreezeTypeParams::fromEncodable(Arr::get($decoded, 'freezeType'))->toArray(),
        ];
    }

    public function setAttribute(string $data): array
    {
        $decoded = $this->codec->process('SetAttribute', new ScaleBytes($data));

        return [
            'collectionId' => gmp_strval(Arr::get($decoded, 'collectionId')),
            'tokenId' => ($value = Arr::get($decoded, 'tokenId')) !== null ? gmp_strval($value) : null,
            'key' => HexConverter::hexToString(Arr::get($decoded, 'key')),
            'value' => HexConverter::hexToString(Arr::get($decoded, 'value')),
        ];
    }

    public function removeAttribute(string $data): array
    {
        $decoded = $this->codec->process('RemoveAttribute', new ScaleBytes($data));

        return [
            'collectionId' => gmp_strval(Arr::get($decoded, 'collectionId')),
            'tokenId' => ($value = Arr::get($decoded, 'tokenId')) !== null ? gmp_strval($value) : null,
            'key' => HexConverter::hexToString(Arr::get($decoded, 'key')),
        ];
    }

    public function bytes(string $data)
    {
        return $this->codec->process('Bytes', new ScaleBytes($data));
    }

    public function attributeStorageKey(string $data): array
    {
        $decoded = $this->codec->process('AttributeStorage', new ScaleBytes($data));

        return [
            'collectionId' => gmp_strval(Arr::get($decoded, 'collectionId')),
            'tokenId' => ($value = Arr::get($decoded, 'tokenId')) !== null ? gmp_strval($value) : null,
            'attribute' => Arr::get($decoded, 'attribute'),
        ];
    }

    public function pendingCollectionTransferStorageKey(string $data): array
    {
        $decoded = $this->codec->process('PendingCollectionTransfersStorageKey', new ScaleBytes($data));

        return [
            'collectionId' => gmp_strval(Arr::get($decoded, 'collectionId')),
        ];
    }

    public function pendingCollectionTransferStorageData(string $data): array
    {
        $decoded = $this->codec->process('PendingCollectionTransfersStorageData', new ScaleBytes($data));

        return [
            'accountId' => HexConverter::prefix(Arr::get($decoded, 'account')),
        ];
    }

    public function collectionStorageKey(string $data): array
    {
        $decoded = $this->codec->process('CollectionStorageKey', new ScaleBytes($data));

        return [
            'collectionId' => gmp_strval(Arr::get($decoded, 'collectionId')),
        ];
    }

    public function getValue(array $data, array $keys, ?string $prefix = null, ?string $suffix = null): mixed
    {
        foreach ($keys as $key) {
            $keyValue = $prefix ? "{$prefix}.{$key}" : $key;
            $keyValue .= $suffix ? ".{$suffix}" : '';

            if (Arr::has($data, $keyValue)) {
                return Arr::get($data, $keyValue);
            }
        }

        return null;
    }

    public function collectionStorageData(string $data): array
    {
        $decoded = $this->codec->process('CollectionStorageData', new ScaleBytes($data));

        return [
            'owner' => ($owner = Arr::get($decoded, 'owner')) !== null ? HexConverter::prefix($owner) : null,
            'maxTokenCount' => ($value = Arr::get($decoded, 'policy.mint.maxTokenCount')) !== null ? gmp_strval($value) : null,
            'maxTokenSupply' => ($value = Arr::get($decoded, 'policy.mint.maxTokenSupply')) !== null ? gmp_strval($value) : null,
            'forceCollapsingSupply' => Arr::get($decoded, 'policy.mint.forceCollapsingSupply'),
            'burn' => Arr::get($decoded, 'policy.burn'),
            'isFrozen' => Arr::get($decoded, 'policy.transfer.isFrozen'),
            'royaltyBeneficiary' => ($beneficiary = Arr::get($decoded, 'policy.market.royalty.beneficiary')) !== null ? HexConverter::prefix($beneficiary) : null,
            'royaltyPercentage' => ($percentage = Arr::get($decoded, 'policy.market.royalty.percentage')) !== null ? $percentage / 10 ** 7 : null,
            'attribute' => Arr::get($decoded, 'policy.attribute'),
            'tokenCount' => gmp_strval(Arr::get($decoded, 'tokenCount')),
            'attributeCount' => gmp_strval(Arr::get($decoded, 'attributeCount')),
            'creationDepositor' => HexConverter::prefix(Arr::get($decoded, 'creationDeposit.depositor')),
            'creationDepositAmount' => gmp_strval(Arr::get($decoded, 'creationDeposit.amount')),
            'totalDeposit' => gmp_strval(Arr::get($decoded, 'totalDeposit')),
            'totalInfusion' => gmp_strval(Arr::get($decoded, 'totalInfusion')),
            'explicitRoyaltyCurrencies' => Arr::get($decoded, 'explicitRoyaltyCurrencies'),
            // TODO: New things from v1020
            'tokenGroupCount' => ($value = Arr::get($decoded, 'tokenGroupCount')) !== null ? gmp_strval($value) : null,
            'royalties' => Arr::get($decoded, 'policy.market.royalty.beneficiaries'),
        ];
    }

    public function tokenStorageKey(string $data): array
    {
        $decoded = $this->codec->process('TokenStorageKey', new ScaleBytes($data));

        return [
            'collectionId' => gmp_strval(Arr::get($decoded, 'collectionId')),
            'tokenId' => gmp_strval(Arr::get($decoded, 'tokenId')),
        ];
    }

    public function tokenStorageData(string $data): array
    {
        $decoded = $this->codec->process('TokenStorageData', new ScaleBytes($data));

        $cap = TokenMintCapType::tryFrom(collect(Arr::get($decoded, 'cap'))->keys()->first());
        $capSupply = Arr::get($decoded, 'cap.Supply') ?? Arr::get($decoded, 'cap.CollapsingSupply');
        $isCurrency = Arr::exists(Arr::get($decoded, 'marketBehavior') ?: [], 'IsCurrency');
        $isFrozen = in_array(Arr::get($decoded, 'freezeState'), ['Permanent', 'Temporary']);

        return [
            'supply' => gmp_strval(Arr::get($decoded, 'supply')),
            'cap' => $cap,
            'capSupply' => $capSupply !== null ? gmp_strval($capSupply) : null,
            'isFrozen' => $isFrozen,
            'royaltyBeneficiary' => ($beneficiary = Arr::get($decoded, 'marketBehavior.HasRoyalty.beneficiary')) !== null ? HexConverter::prefix($beneficiary) : null,
            'royaltyPercentage' => ($percentage = Arr::get($decoded, 'marketBehavior.HasRoyalty.percentage')) !== null ? $percentage / 10 ** 7 : null,
            'isCurrency' => $isCurrency,
            'listingForbidden' => Arr::get($decoded, 'listingForbidden'),
            'attributeCount' => gmp_strval(Arr::get($decoded, 'attributeCount')),
            'accountCount' => gmp_strval(Arr::get($decoded, 'accountCount')),
            'requiresDeposit' => Arr::get($decoded, 'requiresDeposit'),
            'creationDepositor' => ($depositor = Arr::get($decoded, 'creationDeposit.depositor')) !== null ? HexConverter::prefix($depositor) : null,
            'creationDepositAmount' => gmp_strval(Arr::get($decoded, 'creationDeposit.amount')),
            'ownerDeposit' => gmp_strval(Arr::get($decoded, 'ownerDeposit')),
            'totalTokenAccountDeposit' => gmp_strval(Arr::get($decoded, 'totalTokenAccountDeposit')),
            'infusion' => gmp_strval(Arr::get($decoded, 'infusion')),
            'anyoneCanInfuse' => Arr::get($decoded, 'anyoneCanInfuse'),
            'decimalCount' => gmp_strval(Arr::get($decoded, 'metadata.decimalCount')),
            'name' => Arr::get($decoded, 'metadata.name'),
            'symbol' => Arr::get($decoded, 'metadata.symbol'),
            // TODO: New things from v1020
            'royalties' => Arr::get($decoded, 'marketBehavior.HasRoyalty.beneficiaries'),
        ];

    }

    public function collectionAccountStorageKey(string $data): array
    {
        $decoded = $this->codec->process('CollectionAccountsStorageKey', new ScaleBytes($data));

        return [
            'collectionId' => gmp_strval(Arr::get($decoded, 'collectionId')),
            'accountId' => HexConverter::prefix(Arr::get($decoded, 'accountId')),
        ];
    }

    public function collectionAccountStorageData(string $data): array
    {
        $decoded = $this->codec->process('CollectionAccountsStorageData', new ScaleBytes($data));

        $approvals = collect(Arr::get($decoded, 'approvals'))->map(
            fn ($expiration, $account) => [
                'accountId' => HexConverter::prefix($account),
                'expiration' => $expiration !== null ? gmp_strval($expiration) : null,
            ]
        )->values()->toArray();

        return [
            'isFrozen' => Arr::get($decoded, 'isFrozen'),
            'approvals' => $approvals,
            'accountCount' => gmp_strval(Arr::get($decoded, 'accountCount')),
        ];
    }

    public function tokenAccountStorageKey(string $data): array
    {
        $decoded = $this->codec->process('TokenAccountsStorageKey', new ScaleBytes($data));

        return [
            'accountId' => HexConverter::prefix(Arr::get($decoded, 'accountId')),
            'collectionId' => gmp_strval(Arr::get($decoded, 'collectionId')),
            'tokenId' => gmp_strval(Arr::get($decoded, 'tokenId')),
        ];
    }

    public function tokenAccountStorageData(string $data): array
    {
        $decoded = $this->codec->process('TokenAccountsStorageData', new ScaleBytes($data));
        $approvals = collect(Arr::get($decoded, 'approvals'))->map(
            fn ($approval, $account) => [
                'accountId' => HexConverter::prefix($account),
                'amount' => gmp_strval($approval['amount']),
                'expiration' => ($expiration = $approval['expiration']) !== null ? gmp_strval($expiration) : null,
            ]
        )->values()->toArray();

        $namedReserves = array_map(
            fn ($holdReason) => [
                'pallet' => RuntimeHoldReason::fromIndex(Arr::get($holdReason, 'index')),
                'amount' => gmp_strval(Arr::get($holdReason, 'balance')),
            ],
            Arr::get($decoded, 'holds')
        );

        return [
            'balance' => gmp_strval(Arr::get($decoded, 'balance')),
            'reservedBalance' => gmp_strval(Arr::get($decoded, 'reservedBalance')),
            'lockedBalance' => gmp_strval(Arr::get($decoded, 'lockedBalance')),
            'namedReserves' => $namedReserves,
            'approvals' => $approvals,
            'isFrozen' => Arr::get($decoded, 'isFrozen'),
            // TODO: To implement at v1010
            //            'depositDepositor' => Arr::get($decoded, 'deposit.depositor'),
            //            'depositAmount' => gmp_strval(Arr::get($decoded, 'deposit.amount')),
        ];
    }

    /**
     * Decodes the given data by key.
     */
    public function tankStorageKey(string $data): array
    {
        $decoded = $this->codec->process('TankStorageKey', new ScaleBytes($data));

        return [
            'tankAccount' => ($tankAccount = Arr::get($decoded, 'tankAccount')) !== null ? HexConverter::prefix($tankAccount) : null,
        ];
    }

    /**
     * Decodes the given data.
     */
    public function tankStorageData(string $data): array
    {
        $type = currentSpec() >= 1030 ? 'TankStorageData' : 'TankStorageDataV1022';
        $decoded = $this->codec->process($type, new ScaleBytes($data));

        return [
            'owner' => ($owner = Arr::get($decoded, 'owner')) !== null ? HexConverter::prefix($owner) : null,
            'name' => ($name = Arr::get($decoded, 'name')) !== null ? HexConverter::hexToString($name) : null,
            'ruleSets' => $this->parseRuleSets(Arr::get($decoded, 'ruleSets', [])),
            'totalReserved' => gmp_strval(Arr::get($decoded, 'totalReserved')),
            'accountCount' => gmp_strval(Arr::get($decoded, 'accountCount')),
            'coveragePolicy' => CoveragePolicy::from(Arr::get($decoded, 'coveragePolicy')),
            'reservesAccountCreationDeposit' => Arr::get($decoded, 'userAccountManagement.tankReservesAccountCreationDeposit'),
            'isFrozen' => Arr::get($decoded, 'isFrozen'),
            'accountRules' => $this->parseAccountRules(Arr::get($decoded, 'accountRules')),
        ];
    }

    /**
     * Decodes fuel tank account by key.
     */
    public function fuelTankAccountStorageKey(string $data): array
    {
        $decoded = $this->codec->process('FuelTankAccountStorageKey', new ScaleBytes($data));

        return [
            'tankAccount' => ($tank = Arr::get($decoded, 'tankAccount')) !== null ? HexConverter::prefix($tank) : null,
            'userAccount' => ($user = Arr::get($decoded, 'account')) !== null ? HexConverter::prefix($user) : null,
        ];
    }

    /**
     * Decodes fuel tank account by data.
     */
    public function fuelTankAccountStorageData(string $data): array
    {
        $decoded = $this->codec->process('FuelTankAccountStorageData', new ScaleBytes($data));

        return [
            'tankDeposit' => gmp_strval(Arr::get($decoded, 'tankDeposit')),
            'userDeposit' => gmp_strval(Arr::get($decoded, 'userDeposit')),
            'totalReceived' => gmp_strval(Arr::get($decoded, 'totalReceived')),
            'ruleDataSets' => '', // TODO: Implement
        ];
    }

    /**
     * Returns the decoded listing storage key.
     */
    public function listingStorageKey(string $data): array
    {
        $decoded = $this->codec->process('ListingStorageKey', new ScaleBytes($data));

        return [
            'listingId' => ($listingId = Arr::get($decoded, 'listingId')) !== null ? HexConverter::prefix($listingId) : null,
        ];
    }

    /**
     * Returns the decoded listing storage data.
     */
    public function listingStorageData(string $data): array
    {
        $decoded = $this->codec->process('ListingStorageData', new ScaleBytes($data));

        return [
            'seller' => ($seller = $this->getValue($decoded, ['seller', 'creator'])) !== null ? HexConverter::prefix($seller) : null,
            'makeAssetId' => MultiTokensTokenAssetIdParams::fromEncodable(Arr::get($decoded, 'makeAssetId')),
            'takeAssetId' => MultiTokensTokenAssetIdParams::fromEncodable(Arr::get($decoded, 'takeAssetId')),
            'amount' => gmp_strval(Arr::get($decoded, 'amount')),
            'price' => gmp_strval(Arr::get($decoded, 'price')),
            'minTakeValue' => gmp_strval($this->getValue($decoded, ['minTakeValue', 'minReceived'])),
            'feeSide' => FeeSide::from(Arr::get($decoded, 'feeSide', 'NoFee')),
            'creationBlock' => gmp_strval(Arr::get($decoded, 'creationBlock')),
            'deposit' => gmp_strval($this->getValue($decoded, ['deposit.amount', 'deposit'])),
            'salt' => Arr::get($decoded, 'salt'),
            'data' => Arr::get($decoded, 'data'),
            'state' => Arr::get($decoded, 'state'),
            // TODO: This are new fields added in v1010
            //      'depositDepositor' => Arr::get($decoded, 'deposit.depositor'),
            //      'data' => Now has FixedPrice, Auction, Offer
            //          FixedPrice = boolean
            //          Auction = { startBlock: Compact<u32>, endBlock: Compact<u32> }
            //          Offer = { expiration: Option<u32> }
            //      'state' => Now has FixedPrice, Auction, Offer
            //          FixedPrice = { amountFilled: Compact<u128> }
            //          Auction = { highBid: Option<Bid> }
            //              Bid = { bidder: AccountId, price: Compact<u128> }
            //          Offer = { counter: Option<CounterOffer> }
            //              CounterOffer = { accountId: AccountId, price: u128 }
        ];
    }

    /**
     * Parses the rule sets.
     */
    protected function parseRuleSets(array $ruleSets): array
    {
        if (empty($ruleSets)) {
            return [];
        }

        return array_map(
            fn ($setId, $ruleSet) => (new DispatchRulesParams())->fromEncodable($setId, $ruleSet),
            array_keys($ruleSets),
            $ruleSets,
        );
    }

    /**
     * Parses the account rules.
     */
    protected function parseAccountRules(array $accountRules): ?AccountRulesParams
    {
        if (empty($accountRules)) {
            return null;
        }

        return (new AccountRulesParams())->fromEncodable($accountRules);
    }
}
