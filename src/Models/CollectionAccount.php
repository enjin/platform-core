<?php

namespace Enjin\Platform\Models;

use Enjin\Platform\Models\Traits\EagerLoadSelectFields;
use Enjin\Platform\Models\Traits\Unwritable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollectionAccount extends Model
{
    use EagerLoadSelectFields;
    use Unwritable;

    public $incrementing = false;
    protected $connection = 'indexer';
    protected $table = 'collection_account';
    protected $keyType = 'string';


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

    /**
     * The collection relationship.
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * The wallet relationship.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * The collection account approvals relationship.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(CollectionAccountApproval::class);
    }


    //    public $fillable = [
    //        'collection_id',
    //        'wallet_id',
    //        'is_frozen',
    //        'account_count',
    //        'created_at',
    //        'updated_at',
    //    ];
    //
    //    /**
    //     * The model's attributes.
    //     *
    //     * @var array
    //     */
    //    protected $attributes = [
    //        'is_frozen' => false,
    //        'account_count' => 0,
    //    ];

    protected function casts(): array
    {
        return [
            'is_frozen' => 'boolean',
            'approvals' => 'array',
        ];
    }
}
