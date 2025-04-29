<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\Platform\Database\Factories\MarketplaceSaleFactory;
use Enjin\Platform\Models\BaseModel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceSale extends BaseModel
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
        'wallet_id',
        'price',
        'amount',
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
     * The bidder wallet's relationship.
     */
    public function bidder(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    /**
     * The listing's relationship.
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'listing_chain_id', 'listing_chain_id');
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<static>
     */
    protected static function newFactory()
    {
        return MarketplaceSaleFactory::new();
    }

    #[\Override]
    protected function pivotIdentifier(): Attribute
    {
        return new Attribute(
            get: fn () => $this->id
        );
    }

    #[\Override]
    protected function ownerId(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->loadMissing('listing')?->listing?->seller_wallet_id,
        );
    }
}
