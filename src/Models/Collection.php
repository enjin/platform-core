<?php

namespace Enjin\Platform\Models;

use Enjin\Platform\Database\Factories\Unwritable\CollectionFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\Factory;

class Collection extends UnwritableModel
{
    protected $table = 'collection';

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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'owner_id');
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class);
    }

    public function collectionAccounts(): HasMany
    {
        return $this->hasMany(CollectionAccount::class, 'collection_id');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class, 'collection_id')->whereNull('token_id');
    }

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

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory|CollectionFactory
    {
        return CollectionFactory::new();
    }
}
