<?php

namespace Enjin\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
