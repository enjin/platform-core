<?php

namespace Enjin\Platform\Models\Indexer;

use Enjin\Platform\Database\Factories\Unwritable\BidFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bid extends UnwritableModel
{
    /**
     * The table name in the indexer database.
     */
    protected $table = 'bid';

    /**
     * The columns from the table.
     */
    protected $visible = [
        'id',
        'price',
        'height',
        'extrinsic_hash',
        'created_at',
        'bidder_id',
        'listing_id',
    ];

    /**
     * The account this bid belongs to.
     */
    public function bidder(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bidder_id');
    }

    /**
     * The listing this bid belongs to.
     */
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
