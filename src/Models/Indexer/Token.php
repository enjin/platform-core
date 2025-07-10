<?php

namespace Enjin\Platform\Models\Indexer;

use Enjin\Platform\Database\Factories\Unwritable\TokenFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Token extends UnwritableModel
{
    /**
     * The table name in the indexer database.
     */
    protected $table = 'token';

    /**
     * The columns from the table.
     */
    protected $visible = [
        'id',
        'token_id',
        'supply',
        'is_frozen',
        'freeze_state',
        'cap',
        'behavior',
        'listing_forbidden',
        'native_metadata',
        'unit_price',
        'minimum_balance',
        'mint_deposit',
        'attribute_count',
        'account_deposit_count',
        'anyone_can_infuse',
        'infusion',
        'name',
        'non_fungible',
        'metadata',
        'updated_at',
        'created_at',
        'collection_id',
        'best_listing_id',
        'recent_listing_id',
        'last_sale_id',
    ];

    /**
     * The collection this token belongs to.
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * The token accounts this token has.
     */
    public function tokenAccounts(): HasMany
    {
        return $this->hasMany(TokenAccount::class);
    }

    /**
     * The attributes this token has.
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class, 'token_id');
    }

    protected function casts(): array
    {
        return [
            'is_frozen' => 'boolean',
            'cap' => 'array',
            'behavior' => 'array',
            'listing_forbidden' => 'boolean',
            'native_metadata' => 'array',
            'anyone_can_infuse' => 'boolean',
            'non_fungible' => 'boolean',
            'metadata' => 'array',
        ];
    }

    //    public $fillable = [
    //        'collection_id',
    //        'token_chain_id',
    //        'supply',
    //        'cap',
    //        'cap_supply',
    //        'is_frozen',
    //        'royalty_wallet_id',
    //        'royalty_percentage',
    //        'is_currency',
    //        'listing_forbidden',
    //        'requires_deposit',
    //        'creation_depositor',
    //        'creation_deposit_amount',
    //        'owner_deposit',
    //        'total_token_account_deposit',
    //        'attribute_count',
    //        'account_count',
    //        'infusion',
    //        'anyone_can_infuse',
    //        'decimal_count',
    //        'name',
    //        'symbol',
    //        'created_at',
    //        'updated_at',
    //    ];
    //


    //    /**
    //     * The metadata attribute accessor.
    //     */
    //    protected function fetchMetadata(): Attribute
    //    {
    //        return new Attribute(
    //            get: fn () => $this->attributes['fetch_metadata'] ?? false,
    //            set: function ($value): void {
    //                if ($value === true) {
    //                    $this->attributes['metadata'] = MetadataService::getCache($this->getRelation('attributes')->first());
    //                }
    //                $this->attributes['fetch_metadata'] = $value;
    //            }
    //        );
    //    }
    //
    //    /**
    //     * The metadata attribute accessor.
    //     */
    //    protected function metadata(): Attribute
    //    {
    //        return new Attribute(
    //            get: fn () => $this->attributes['metadata']
    //                ?? MetadataService::getCache($this->fetchUriAttribute($this)?->value_string ?? '')
    //                ?? MetadataService::getCache($this->fetchUriAttribute($this->collection)->value_string ?? ''),
    //        );
    //    }
    //
    //    #[\Override]
    //    protected function ownerId(): Attribute
    //    {
    //        if (!$this->loadMissing('collection')->collection) {
    //            throw new PlatformException(__('enjin-platform::error.no_collection', ['tokenId' => $this->token_chain_id]));
    //        }
    //
    //        return Attribute::make(
    //            get: fn () => $this->loadMissing('collection')->collection?->owner_wallet_id,
    //        );
    //    }
    //
    //    private function fetchUriAttribute($model)
    //    {
    //        if (!$model->relationLoaded('attributes')) {
    //            $model->load('attributes');
    //        }
    //
    //        return $model->getRelation('attributes')
    //            ->filter(fn ($attribute) => $attribute->key == 'uri' || $attribute->key == HexConverter::stringToHexPrefixed('uri'))
    //            ->first();
    //    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory|TokenFactory
    {
        return TokenFactory::new();
    }
}
