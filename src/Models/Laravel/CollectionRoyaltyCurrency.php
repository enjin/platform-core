<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\Platform\Database\Factories\CollectionRoyaltyCurrencyFactory;
use Enjin\Platform\Models\BaseModel;
use Enjin\Platform\Models\Laravel\Traits\CollectionRoyaltyCurrency as CollectionRoyaltyCurrencyMethods;
use Enjin\Platform\Models\Laravel\Traits\EagerLoadSelectFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CollectionRoyaltyCurrency extends BaseModel
{
    use HasFactory;
    use CollectionRoyaltyCurrencyMethods;
    use EagerLoadSelectFields;

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
