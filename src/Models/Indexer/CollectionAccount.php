<?php

namespace Enjin\Platform\Models\Indexer;

use Enjin\Platform\Database\Factories\Unwritable\CollectionAccountFactory;
use Enjin\Platform\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionAccount extends UnwritableModel
{
    /**
     * The table name in the indexer database.
     */
    protected $table = 'collection_account';

    /**
     * The columns from the table.
     */
    protected $visible = [
        'id',
        'is_frozen',
        'approvals',
        'account_count',
        'created_at',
        'updated_at',
        'account_id',
        'collection_id',
    ];

    //    public $fillable = [
    //        'collection_id',
    //        'wallet_id',
    //        'is_frozen',
    //        'account_count',
    //        'created_at',
    //        'updated_at',
    //    ];

    /**
     * The collection this collection account belongs to.
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * The account this collection account belongs to.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    protected function casts(): array
    {
        return [
            'is_frozen' => 'boolean',
            'approvals' => 'array',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory|CollectionAccountFactory
    {
        return CollectionAccountFactory::new();
    }
}
