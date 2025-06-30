<?php

namespace Enjin\Platform\Models\Indexer;

use Enjin\Platform\Database\Factories\Unwritable\CollectionFactory;
use Enjin\Platform\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute as CastAttribute;

class Collection extends UnwritableModel
{
    /**
     * The table name in the indexer database.
     */
    protected $table = 'collection';

    /**
     * The columns from the table.
     */
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

    /**
     * The account this collection belongs to.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'owner_id');
    }

    /**
     * The tokens this collection has.
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class);
    }

    /**
     * The collection accounts this collection has.
     */
    public function collectionAccounts(): HasMany
    {
        return $this->hasMany(CollectionAccount::class, 'collection_id');
    }

    /**
     * The attributes this collection has.
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class, 'collection_id')->whereNull('token_id');
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

    protected function network(): CastAttribute
    {
        return new CastAttribute(
            get: fn ($value) => currentMatrix()->name,
        );
    }

    protected function casts(): array
    {
        return [
            'mint_policy' => 'array',
            'market_policy' => 'array',
            'transfer_policy' => 'array',
            'created_at' => 'timestamp',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory|CollectionFactory
    {
        return CollectionFactory::new();
    }
}
