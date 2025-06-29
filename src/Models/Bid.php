<?php

namespace Enjin\Platform\Models;

use Enjin\Platform\Database\Factories\Unwritable\BidFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\Factory;

class Bid extends UnwritableModel
{
    protected $table = 'bid';

    protected $visible = [
        'id',
        'price',
        'height',
        'extrinsic_hash',
        'created_at',
        'bidder_id',
        'listing_id',
    ];

    public function bidder(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'bidder_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'listing_id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory|BidFactory
    {
        return BidFactory::new();
    }
}
