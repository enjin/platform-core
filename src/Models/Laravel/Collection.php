<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\Platform\Database\Factories\CollectionFactory;
use Enjin\Platform\Models\BaseModel;
use Enjin\Platform\Models\Laravel\Traits\Collection as CollectionMethods;
use Enjin\Platform\Models\Laravel\Traits\EagerLoadSelectFields;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Staudenmeir\EloquentEagerLimit\HasEagerLimit;

class Collection extends BaseModel
{
    use CollectionMethods;
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
    protected $fillable = [
        'collection_chain_id',
        'owner_wallet_id',
        'max_token_count',
        'max_token_supply',
        'force_single_mint',
        'is_frozen',
        'royalty_wallet_id',
        'royalty_percentage',
        'token_count',
        'attribute_count',
        'total_deposit',
        'network',
        'created_at',
        'updated_at',
    ];

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [
        'force_single_mint' => false,
        'is_frozen' => false,
        'token_count' => '0',
        'attribute_count' => '0',
        'total_deposit' => '0',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CollectionFactory
    {
        return CollectionFactory::new();
    }

    protected function pivotIdentifier(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->collection_chain_id,
        );
    }
}
