<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\Platform\Database\Factories\MarketplaceBidFactory;
use Enjin\Platform\Models\BaseModel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceBid extends BaseModel
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
        'marketplace_listing_id',
        'wallet_id',
        'price',
        'height',
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
        return $this->belongsTo(MarketplaceListing::class, 'marketplace_listing_id');
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<static>
     */
    protected static function newFactory()
    {
        return MarketplaceBidFactory::new();
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