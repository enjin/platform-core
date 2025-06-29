<?php

namespace Enjin\Platform\Models;

use Enjin\Platform\Database\Factories\Unwritable\ListingFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\Factory;

class Listing extends UnwritableModel
{
    protected $table = 'listing';

    protected $visible = [
        'id',
        'amount',
        'price',
        'min_take_value',
        'fee_side',
        'height',
        'deposit',
        'salt',
        'data',
        'state',
        'start_block',
        'creation_block',
        'uses_whitelist',
        'highest_price',
        'dead_listing',
        'is_active',
        'type',
        'has_royalty_increased',
        'created_at',
        'updated_at',
        'seller_id',
        'make_asset_id_id',
        'take_asset_id_id',
    ];

    //    protected $fillable = [
    //        'listing_chain_id',
    //        'seller_wallet_id',
    //        'make_collection_chain_id',
    //        'make_token_chain_id',
    //        'take_collection_chain_id',
    //        'take_token_chain_id',
    //        'amount',
    //        'price',
    //        'min_take_value',
    //        'fee_side',
    //        'creation_block',
    //        'deposit',
    //        'salt',
    //        'type',
    //        'auction_start_block',
    //        'auction_end_block',
    //        'offer_expiration',
    //        'counter_offer_count',
    //        'amount_filled',
    //    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'seller_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'listing_chain_id', 'listing_chain_id');
    }

    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class);
    }

    public function states(): HasMany
    {
        return $this->hasMany(MarketplaceState::class);
    }

    public function highestBid()
    {
        return $this->hasOne(Bid::class)->ofMany('price', 'max');
    }

    public function state()
    {
        return $this->hasOne(MarketplaceState::class)->latestOfMany();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory|ListingFactory
    {
        return ListingFactory::new();
    }
}
