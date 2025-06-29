<?php

namespace Enjin\Platform\Models;

use Enjin\Platform\Database\Factories\Unwritable\CollectionAccountFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollectionAccount extends UnwritableModel
{
    protected $table = 'collection_account';

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

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    //    public $fillable = [
    //        'collection_id',
    //        'wallet_id',
    //        'is_frozen',
    //        'account_count',
    //        'created_at',
    //        'updated_at',
    //    ];

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
