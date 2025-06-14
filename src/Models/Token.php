<?php

namespace Enjin\Platform\Models;

use Enjin\Platform\Models\Traits\EagerLoadSelectFields;
use Enjin\Platform\Models\Traits\Unwritable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Token extends Model
{
    use EagerLoadSelectFields;
    use Unwritable;

    public $incrementing = false;

    protected $connection = 'indexer';
    protected $table = 'token';
    protected $keyType = 'string';

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
     * The collection relationship.
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * The creation depositor relationship.
     */
    public function creationDepositor(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'creation_depositor');
    }

    /**
     * The royalty benificiary relationship.
     */
    public function royaltyBeneficiary(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'royalty_wallet_id');
    }

    /**
     * The token accounts relationship.
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(TokenAccount::class);
    }

    /**
     * The attributes relationship.
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
    //     * The model's attributes.
    //     *
    //     * @var array
    //     */
    //    protected $attributes = [
    //        'supply' => '1',
    //        'is_frozen' => false,
    //        'is_currency' => false,
    //        'listing_forbidden' => false,
    //        'requires_deposit' => true,
    //        'creation_deposit_amount' => '0',
    //        'owner_deposit' => '0',
    //        'total_token_account_deposit' => '0',
    //        'attribute_count' => 0,
    //        'account_count' => 0,
    //        'infusion' => '0',
    //        'anyone_can_infuse' => false,
    //        'decimal_count' => 0,
    //    ];

    /**
     * The non-fungible attribute accessor.
     */
    //    public function nonFungible(): Attribute
    //    {
    //        return new Attribute(
    //            get: fn () => $this->isNonFungible()
    //        );
    //    }
    //
    //    /**
    //     * Checks if the token is non-fungible.
    //     */
    //    protected function isNonFungible(): bool
    //    {
    //        if ($this->is_currency) {
    //            // If the token is a currency it is fungible.
    //            return false;
    //        }
    //
    //        if ($this->collection->max_token_supply === '1') {
    //            // If the collection has a rule of maxTokenSupply of 1 means all tokens are NFT
    //            return true;
    //        }
    //
    //        if ($this->collection->force_collapsing_supply && $this->supply === '1') {
    //            // If the collection has a rule of forceSingleMint and there is only one unit of the token means it is a NFT
    //            return true;
    //        }
    //
    //        if ($this->cap === TokenMintCapType::SUPPLY->name) {
    //            // If token has a cap of Supply 1, it is non-fungible.
    //            // If the cap Supply is more than 1, it is fungible.
    //            return $this->cap_supply === '1';
    //        }
    //
    //        if ($this->cap === TokenMintCapType::COLLAPSING_SUPPLY->name) {
    //            // If the token is set as SingleMint and only one was minted it is non-fungible
    //            // If more than one was minted it is fungible.
    //            return $this->cap_supply === '1';
    //        }
    //
    //        // All other cases we will consider the token is fungible.
    //        return false;
    //    }
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
    //    /**
    //     * Create a new factory instance for the model.
    //     */
    //    protected static function newFactory(): TokenFactory
    //    {
    //        return TokenFactory::new();
    //    }
    //
    //    #[\Override]
    //    protected function pivotIdentifier(): Attribute
    //    {
    //        if (!$this->relationLoaded('collection')) {
    //            $this->load('collection:id,collection_chain_id');
    //        }
    //
    //        if (!$collection = $this->collection) {
    //            throw new PlatformException(__('enjin-platform::error.no_collection', ['tokenId' => $this->token_chain_id]));
    //        }
    //
    //        return Attribute::make(
    //            get: fn () => "{$collection->collection_chain_id}:{$this->token_chain_id}",
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
}
