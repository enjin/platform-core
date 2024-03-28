<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\Platform\Database\Factories\CollectionAccountFactory;
use Enjin\Platform\Models\BaseModel;
use Enjin\Platform\Models\Laravel\Traits\CollectionAccount as CollectionAccountMethods;
use Enjin\Platform\Models\Laravel\Traits\EagerLoadSelectFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Staudenmeir\EloquentEagerLimit\HasEagerLimit;

class CollectionAccount extends BaseModel
{
    use CollectionAccountMethods;
    use EagerLoadSelectFields;
    use HasEagerLimit;
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    public $guarded = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    public $fillable = [
        'collection_id',
        'wallet_id',
        'is_frozen',
        'account_count',
        'created_at',
        'updated_at',
    ];

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [
        'is_frozen' => false,
        'account_count' => 0,
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CollectionAccountFactory
    {
        return CollectionAccountFactory::new();
    }
}
