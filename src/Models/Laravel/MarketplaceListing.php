<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\Platform\Marketplace\Database\Factories\MarketplaceListingFactory;
use Enjin\Platform\Models\BaseModel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceListing extends BaseModel
{
    use HasFactory;
    use Traits\EagerLoadSelectFields;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    public $guarded = [];

    /**
     * The fillable fields.
     *
     * @var array
     */
    protected $fillable = [
        'listing_chain_id',
        'seller_wallet_id',
        'make_collection_chain_id',
        'make_token_chain_id',
        'take_collection_chain_id',
        'take_token_chain_id',
        'amount',
        'price',
        'min_take_value',
        'fee_side',
        'creation_block',
        'deposit',
        'salt',
        'type',
        'auction_start_block',
        'auction_end_block',
        'offer_expiration',
        'counter_offer_count',
        'amount_filled',
    ];

    /**
     * The hidden fields.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * The seller wallet's relationship.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'seller_wallet_id');
    }

    /**
     * The sales relationship.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(MarketplaceSale::class, 'listing_chain_id', 'listing_chain_id');
    }

    /**
     * The bids relationship.
     */
    public function bids(): HasMany
    {
        return $this->hasMany(MarketplaceBid::class);
    }

    /**
     * The listing state's relationship.
     */
    public function states(): HasMany
    {
        return $this->hasMany(MarketplaceState::class);
    }

    /**
     * The highest bid relationship.
     */
    public function highestBid()
    {
        return $this->hasOne(MarketplaceBid::class)->ofMany('price', 'max');
    }

    /**
     * The latest state relationship.
     */
    public function state()
    {
        return $this->hasOne(MarketplaceState::class)->latestOfMany();
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<static>
     */
    protected static function newFactory()
    {
        return MarketplaceListingFactory::new();
    }

    #[\Override]
    protected function pivotIdentifier(): Attribute
    {
        return new Attribute(
            get: fn () => $this->listing_chain_id
        );
    }

    #[\Override]
    protected function ownerId(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->seller_wallet_id,
        );
    }
}
