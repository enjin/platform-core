<?php

namespace Enjin\Platform\Models\Indexer;

use Enjin\Platform\Database\Factories\Unwritable\SaleFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends UnwritableModel
{
    /**
     * The table name in the indexer database.
     */
    protected $table = 'listing_sale';

    /**
     * The columns from the table.
     */
    protected $visible = [
        'id',
        'amount',
        'price',
        'created_at',
        'buyer_id',
        'listing_id',
    ];

    /**
     * The account this sale belongs to.
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'buyer_id');
    }

    /**
     * The listing this sale belongs to.
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'listing_id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory|SaleFactory
    {
        return SaleFactory::new();
    }
}
