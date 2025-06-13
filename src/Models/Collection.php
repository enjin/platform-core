<?php

namespace Enjin\Platform\Models;

use Enjin\Platform\Models\Traits\EagerLoadSelectFields;
use Enjin\Platform\Models\Traits\Unwritable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Collection extends BaseModel
{
    use EagerLoadSelectFields;
    use Unwritable;

    public $incrementing = false;
    public $timestamps = false;

    protected $connection = 'indexer';
    protected $table = 'collection';
    protected $keyType = 'string';

    protected $visible = [
        'id',
        'collection_id',
        'mint_policy',
        'market_policy',
        'burn_policy',
        'transfer_policy',
        'attribute_policy',
        'attribute_count',
        'total_deposit',
        'name',
        'metadata',
        'created_at',
        'flags',
        'socials',
        'category',
        'verified_at',
        'hidden',
        'stats',
        'owner_id',
    ];

    protected function casts(): array
    {
        return [
            'mint_policy' => 'array',
            'market_policy' => 'array',
            'transfer_policy' => 'array',
            'metadata' => 'array',
            'created_at' => 'timestamp',
            'flags' => 'array',
            'socials' => 'array',
            'verified_at' => 'timestamp',
            'hidden' => 'boolean',
            'stats' => 'array',
        ];
    }

    // For reference from a previous model
    //    protected $fillable = [
    //        'collection_chain_id',
    //        'owner_wallet_id',
    //        'pending_transfer',
    //        'max_token_count',
    //        'max_token_supply',
    //        'force_collapsing_supply',
    //        'is_frozen',
    //        'royalty_wallet_id',
    //        'royalty_percentage',
    //        'token_count',
    //        'attribute_count',
    //        'creation_depositor',
    //        'creation_deposit_amount',
    //        'total_deposit',
    //        'total_infusion',
    //        'network',
    //        'created_at',
    //        'updated_at',
    //    ];

    // TODO: This should probably be removed later
    protected function pivotIdentifier(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->collection_chain_id,
        );
    }

    /**
     * The wallet owner relationship.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'owner_id');
    }

    /**
     * The creation depositor relationship.
     */
    public function creationDepositor(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'creation_depositor');
    }

    /**
     * The royalty beneficiary relationship.
     */
    public function royaltyBeneficiary(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'royalty_wallet_id');
    }

    /**
     * The royalty currencies relationship.
     */
    public function royaltyCurrencies(): HasMany
    {
        return $this->hasMany(CollectionRoyaltyCurrency::class);
    }

    /**
     * The tokens relationship.
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class);
    }

    /**
     * The collection account relationsip.
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(CollectionAccount::class, 'collection_id');
    }

    /**
     * The attributes relationship.
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(\Enjin\Platform\Models\Attribute::class, 'collection_id')->whereNull('token_id');
    }
}
