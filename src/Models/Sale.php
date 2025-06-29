<?php

namespace Enjin\Platform\Models;

use Enjin\Platform\Database\Factories\Unwritable\SaleFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\Factory;

class Sale extends UnwritableModel
{
    protected $table = 'listing_sale';

    protected $visible = [
        'id',
        'amount',
        'price',
        'created_at',
        'buyer_id',
        'listing_id',
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'buyer_id');
    }

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
