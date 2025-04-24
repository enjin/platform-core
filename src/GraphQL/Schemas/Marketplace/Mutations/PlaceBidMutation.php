<?php

namespace Enjin\Platform\GraphQL\Schemas\Marketplace\Mutations;

use Closure;
use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Facades\TransactionSerializer;
use Enjin\Platform\GraphQL\Schemas\Primary\Substrate\Traits\StoresTransactions;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasSkippableRules;
use Enjin\Platform\GraphQL\Schemas\Primary\Traits\HasTransactionDeposit;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasIdempotencyField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSigningAccountField;
use Enjin\Platform\GraphQL\Types\Input\Substrate\Traits\HasSimulateField;
use Enjin\Platform\Interfaces\PlatformBlockchainTransaction;
use Enjin\Platform\Rules\ListingNotCancelled;
use Enjin\Platform\Rules\MinimumPrice;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Rules\MaxBigInt;
use Enjin\Platform\Rules\MinBigInt;
use Enjin\Platform\Rules\ValidHex;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Rebing\GraphQL\Support\Facades\GraphQL;

class PlaceBidMutation extends MarketplaceMutation implements PlatformBlockchainTransaction
{
    use HasIdempotencyField;
    use HasSigningAccountField;
    use HasSimulateField;
    use HasSkippableRules;
    use HasTransactionDeposit;
    use StoresTransactions;

    /**
     * Get the mutation's attributes.
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'PlaceBid',
            'description' => __('enjin-platform-marketplace::mutation.place_bid.description'),
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
            'listingId' => [
                'type' => GraphQL::type('String!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.field.listingId'),
            ],
            'price' => [
                'type' => GraphQL::type('BigInt!'),
                'description' => __('enjin-platform-marketplace::type.marketplace_listing.field.price'),
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
        $encodedData = TransactionSerializer::encode($this->getMutationName(), static::getEncodableParams(...$args));

        return Transaction::lazyLoadSelectFields(
            DB::transaction(fn () => $this->storeTransaction($args, $encodedData)),
            $resolveInfo
        );
    }

    #[\Override]
    public static function getEncodableParams(...$params): array
    {
        return [
            'listingId' => HexConverter::unPrefix(Arr::get($params, 'listingId', 0)),
            'price' => gmp_init(Arr::get($params, 'price', 0)),
        ];
    }

    /**
     * Get the mutation's validation rules.
     */
    protected function rulesWithValidation(array $args): array
    {
        return [
            'listingId' => [
                'bail',
                'filled',
                'max:255',
                new ListingNotCancelled(),
            ],
            'price' => [
                'bail',
                new MinBigInt(1),
                new MaxBigInt(),
                new MinimumPrice(),
            ],
        ];
    }

    /**
     * Get the mutation's validation rules without DB rules.
     */
    protected function rulesWithoutValidation(array $args): array
    {
        return [
            'listingId' => [
                'bail',
                'filled',
                'max:255',
                new ValidHex(32),
            ],
            'price' => [
                'bail',
                new MinBigInt(1),
                new MaxBigInt(),
            ],
        ];
    }
}
