<?php

namespace Enjin\Platform\Models\Indexer;

use Enjin\Platform\Database\Factories\Unwritable\ExtrinsicFactory;
use Enjin\Platform\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Extrinsic extends UnwritableModel
{
    /**
     * The table name in the indexer database.
     */
    protected $table = 'extrinsic';

    /**
     * The columns from the table.
     */
    protected $visible = [
        'id',
        'hash',
        'block_number',
        'block_hash',
        'success',
        'pallet',
        'method',
        'args',
        'nonce',
        'tip',
        'fee',
        'error',
        'created_at',
        'participants',
        'fuel_tank',
        'signer_id',
    ];

    /**
     * The account this extrinsic belongs to.
     */
    public function signer(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'signer_id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory|ExtrinsicFactory
    {
        return ExtrinsicFactory::new();
    }
}
