<?php

namespace Enjin\Platform\GraphQL\Schemas\Marketplace\Mutations;

use Closure;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\StoresTransactions;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasSkippableRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTokenIdFieldRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTransactionDeposit;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasIdempotencyField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSigningAccountField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSimulateField;
use Enjin\Platform\Interfaces\PlatformBlockchainTransaction;
use Enjin\Platform\Enums\Substrate\ListingType;
use Enjin\Platform\Models\Substrate\AuctionDataParams;
use Enjin\Platform\Models\Substrate\ListingDataParams;
use Enjin\Platform\Models\Substrate\MultiTokensTokenAssetIdParams;
use Enjin\Platform\Models\Substrate\OfferDataParams;
use Enjin\Platform\Rules\EnoughTokenSupply;
use Enjin\Platform\Rules\FutureBlock;
use Enjin\Platform\Rules\TokenExistsInCollection;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Support\Hex;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;

class CreateListingMutation extends MarketplaceMutation implements PlatformBlockchainTransaction
{
    use HasIdempotencyField;
    use HasSigningAccountField;
    use HasSimulateField;
    use HasSkippableRules;
    use HasTokenIdFieldRules;
    use HasTransactionDeposit;
    use StoresTransactions;

    /**
     * Get the mutation's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'CreateListing',
            'description' => __('enjin-platform-marketplace::mutation.create_listing.description'),
        ];
    }

    /**
     * Get the mutation's return type.
     */
    #[\Override]
    public function type(): Type
    {
        return GraphQL::type('Transaction!');
    }

    /**
     * Get the mutation's arguments definition.
     */
    #[\Override]
    public function args(): array
    {
        return [
            'makeAssetId' => [
                'type' => GraphQL::type('MultiTokenIdInput!'),
                'description' => __('enjin-platform-marketplace::mutation.create_listing.args.makeAssetId'),
            ],
            'takeAssetId' => [
                'type' => GraphQL::type('MultiTokenIdInput!'),
                'description' => __('enjin-platform-marketplace::mutation.create_listing.args.takeAssetId'),
            ],
            'amount' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.field.amount'),
            ],
            'price' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.field.price'),
            ],
            'salt' => [
                'type' => GraphQL::type('String'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.field.salt'),
            ],
            'listingData' => [
                'type' => GraphQL::type('ListingDataInput!'),
                'description' => __('enjin-platform-marketplace::mutation.create_listing.args.listingData'),
            ],
            ...$this->getSigningAccountField(),
            ...$this->getIdempotencyField(),
            ...$this->getSimulateField(),
            ...$this->getSkipValidationField(),
        ];
    }

    /**
     * Resolve the mutation's request.
     */
    public function resolve(
        $root,
        array $args,
        $context,
        ResolveInfo $resolveInfo,
        Closure $getSelectFields,
    ) {
        $encodedData = TransactionSerializer::encode($this->getMethodName(), static::getEncodableParams(
            makeAssetId: new MultiTokensTokenAssetIdParams(
                Arr::get($args, 'makeAssetId.collectionId'),
                $this->encodeTokenId(Arr::get($args, 'makeAssetId'))
            ),
            takeAssetId: new MultiTokensTokenAssetIdParams(
                Arr::get($args, 'takeAssetId.collectionId'),
                $this->encodeTokenId(Arr::get($args, 'takeAssetId'))
            ),
            amount: Arr::get($args, 'amount'),
            price: Arr::get($args, 'price'),
            salt: Arr::get($args, 'salt', Str::random(10)),
            listingData: Arr::get($args, 'listingData'),
        ));

        return Transaction::lazyLoadSelectFields(
            DB::transaction(fn () => $this->storeTransaction($args, $encodedData)),
            $resolveInfo
        );
    }

    /**
     * Get the serialization service method name.
     */
    #[\Override]
    public function getMethodName(): string
    {
        return 'CreateListing' . (currentSpec() >= 1030 ? '' : 'V1022');
    }

    #[\Override]
    public static function getEncodableParams(...$params): array
    {
        $makeAsset = Arr::get($params, 'makeAssetId', new MultiTokensTokenAssetIdParams('0', '0'));
        $takeAsset = Arr::get($params, 'takeAssetId', new MultiTokensTokenAssetIdParams('0', '0'));
        $amount = Arr::get($params, 'amount', 0);
        $price = Arr::get($params, 'price', 0);
        $salt = Arr::get($params, 'salt', Str::random(10));
        $listingType = is_string($type = Arr::get($params, 'listingData.type')) ? ListingType::getEnumCase($type) : $type;
        $listingData = match ($listingType) {
            ListingType::AUCTION => new ListingDataParams(
                ListingType::AUCTION,
                auctionParams: new AuctionDataParams(...Arr::get($params, 'listingData.auctionParams')),
            ),
            ListingType::OFFER => new ListingDataParams(
                ListingType::OFFER,
                offerParams: new OfferDataParams(...Arr::get($params, 'listingData.offerParams'))
            ),
            default => new ListingDataParams(ListingType::FIXED_PRICE),
        };

        return [
            'makeAssetId' => $makeAsset->toEncodable(),
            'takeAssetId' => $takeAsset->toEncodable(),
            'amount' => gmp_init($amount),
            'price' => gmp_init($price),
            'startBlock' => null,
            'salt' => HexConverter::stringToHexPrefixed($salt),
            'usesWhitelist' => false,
            'listingData' => $listingData->toEncodable(),
            'depositor' => null,
        ];
    }

    protected function makeOrTakeRuleExist(?string $collectionId = null, ?bool $isMake = true): array
    {
        $makeOrTake = $isMake ? 'makeAssetId' : 'takeAssetId';

        return $collectionId === '0' ? [] : [
            $makeOrTake . '.collectionId' => [
                'bail',
                'required_with:' . $makeOrTake . '.tokenId',
                new MinBigInt(),
                new MaxBigInt(Hex::MAX_UINT128),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (!Collection::where('collection_chain_id', $value)->exists()) {
                        $fail('validation.exists')->translate();
                    }
                },
            ],
        ];
    }

    protected function makeOrTakeRule(?string $collectionId = null, ?bool $isMake = true): array
    {
        $makeOrTake = $isMake ? 'makeAssetId' : 'takeAssetId';

        return $collectionId === '0' ? [] : [
            $makeOrTake . '.collectionId' => [
                'bail',
                'required_with:' . $makeOrTake . '.tokenId',
                new MinBigInt(),
                new MaxBigInt(Hex::MAX_UINT128),
            ],
        ];
    }

    /**
     * Get the common rules.
     */
    protected function rulesCommon(array $args): array
    {
        return [
            'price' => [
                'bail',
                new MinBigInt(),
                new MaxBigInt(),
            ],
            'salt' => ['bail', 'filled', 'max:255'],
            'listingData' => ['required'],
            'listingData.type' => ['required'],
        ];
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        $makeRule = $this->makeOrTakeRuleExist($makeCollection = Arr::get($args, 'makeAssetId.collectionId'));
        $takeRule = $this->makeOrTakeRuleExist($takeCollection = Arr::get($args, 'takeAssetId.collectionId'), false);
        $listingDataType = ListingType::getEnumCase(Arr::get($args, 'listingData.type'));
        $extras = match ($listingDataType) {
            ListingType::AUCTION => [
                'listingData.auctionParams' => ['required'],
                'listingData.offerParams' => ['prohibited'],
                'listingData.auctionParams.startBlock' => ['bail', 'required', new MinBigInt(), new MaxBigInt(Hex::MAX_UINT32), new FutureBlock()],
                'listingData.auctionParams.endBlock' => ['bail', 'required',  new MinBigInt(), new MaxBigInt(Hex::MAX_UINT32), 'gt:listingData.auctionParams.startBlock', new FutureBlock()],
            ],
            ListingType::OFFER => [
                'listingData.offerParams' => ['required'],
                'listingData.auctionParams' => ['prohibited'],
                'listingData.offerParams.expiration' => ['bail', 'nullable', new MinBigInt(), new MaxBigInt(Hex::MAX_UINT32), new FutureBlock()],
            ],
            default => [
                'listingData.auctionParams' => ['prohibited'],
                'listingData.offerParams' => ['prohibited'],
            ],
        };

        return [
            'makeAssetId' => new TokenExistsInCollection($makeCollection),
            ...$makeRule,
            ...$this->getTokenFieldRules('makeAssetId'),
            'takeAssetId' => new TokenExistsInCollection($takeCollection),
            ...$takeRule,
            ...$this->getTokenFieldRules('takeAssetId'),
            'amount' => [
                'bail',
                new MinBigInt(1),
                new MaxBigInt(),
                new EnoughTokenSupply(),
            ],
            ...$extras,
        ];
    }

    /**
     * Get the mutation's validation rules without DB rules.
     */
    protected function rulesWithoutValidation(array $args): array
    {
        $makeRule = $this->makeOrTakeRule(Arr::get($args, 'makeAssetId.collectionId'));
        $takeRule = $this->makeOrTakeRule(Arr::get($args, 'takeAssetId.collectionId'), false);
        $listingDataType = ListingType::getEnumCase(Arr::get($args, 'listingData.type'));
        $extras = match ($listingDataType) {
            ListingType::AUCTION => [
                'listingData.auctionParams' => ['required'],
                'listingData.offerParams' => ['prohibited'],
                'listingData.auctionParams.startBlock' => ['bail', 'required', new MinBigInt(), new MaxBigInt(Hex::MAX_UINT32)],
                'listingData.auctionParams.endBlock' => ['bail', 'required',  new MinBigInt(), new MaxBigInt(Hex::MAX_UINT32), 'gt:listingData.auctionParams.startBlock'],
            ],
            ListingType::OFFER => [
                'listingData.offerParams' => ['required'],
                'listingData.auctionParams' => ['prohibited'],
                'listingData.offerParams.expiration' => ['bail', 'nullable', new MinBigInt(), new MaxBigInt(Hex::MAX_UINT32)],
            ],
            default => [
                'listingData.auctionParams' => ['prohibited'],
                'listingData.offerParams' => ['prohibited'],
            ],
        };

        return [
            ...$makeRule,
            ...$this->getTokenFieldRules('makeAssetId'),
            ...$takeRule,
            ...$this->getTokenFieldRules('takeAssetId'),
            'amount' => [
                'bail',
                new MinBigInt(1),
                new MaxBigInt(),
            ],
            ...$extras,
        ];
    }
}
