<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\Platform\Database\Factories\CollectionFactory;
use Enjin\Platform\Models\BaseModel;
use Enjin\Platform\Models\Laravel\Traits\Collection as CollectionMethods;
use Enjin\Platform\Models\Laravel\Traits\EagerLoadSelectFields;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Collection extends BaseModel
{
    use CollectionMethods;
    use EagerLoadSelectFields;
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
        'pending_transfer',
        'max_token_count',
        'max_token_supply',
        'force_collapsing_supply',
        'is_frozen',
        'royalty_wallet_id',
        'royalty_percentage',
        'token_count',
        'attribute_count',
        'creation_depositor',
        'creation_deposit_amount',
        'total_deposit',
        'total_infusion',
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
        'force_collapsing_supply' => false,
        'is_frozen' => false,
        'token_count' => '0',
        'attribute_count' => '0',
        'total_deposit' => '0',
        'total_infusion' => '0',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CollectionFactory
    {
        return CollectionFactory::new();
    }

    #[\Override]
    protected function pivotIdentifier(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->collection_chain_id,
        );
    }
}
