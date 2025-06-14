<?php

namespace Enjin\Platform\Models;

use Enjin\Platform\Models\Traits\CollectionRoyaltyCurrency as CollectionRoyaltyCurrencyMethods;
use Enjin\Platform\Models\Traits\EagerLoadSelectFields;
use Enjin\Platform\Models\Traits\Unwritable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CollectionRoyaltyCurrency extends BaseModel
{
    use CollectionRoyaltyCurrencyMethods;
    use EagerLoadSelectFields;
    use HasFactory;
    use Unwritable;

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
        'currency_collection_chain_id',
        'currency_token_chain_id',
        'created_at',
        'updated_at',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CollectionRoyaltyCurrencyFactory
    {
        return CollectionRoyaltyCurrencyFactory::new();
    }
}
