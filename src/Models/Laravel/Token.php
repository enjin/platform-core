<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\Platform\Database\Factories\TokenFactory;
use Enjin\Platform\Enums\Substrate\TokenMintCapType;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Models\BaseModel;
use Enjin\Platform\Models\Laravel\Traits\EagerLoadSelectFields;
use Enjin\Platform\Models\Laravel\Traits\Token as TokenMethods;
use Facades\Enjin\Platform\Services\Database\MetadataService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Staudenmeir\EloquentEagerLimit\HasEagerLimit;

class Token extends BaseModel
{
    use EagerLoadSelectFields;
    use HasEagerLimit;
    use HasFactory;
    use TokenMethods;

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
        'token_chain_id',
        'supply',
        'cap',
        'cap_supply',
        'is_frozen',
        'royalty_wallet_id',
        'royalty_percentage',
        'is_currency',
        'listing_forbidden',
        'minimum_balance',
        'unit_price',
        'attribute_count',
        'created_at',
        'updated_at',
    ];

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [
        'supply' => '1',
        'is_frozen' => false,
        'is_currency' => false,
        'listing_forbidden' => false,
        'minimum_balance' => '1',
        'unit_price' => '0',
        'attribute_count' => 0,
    ];

    public function mintDeposit(): Attribute
    {
        return new Attribute(
            get: fn () => gmp_strval(gmp_mul(gmp_init($this->unit_price), gmp_init($this->supply)))
        );
    }

    /**
     * The non-fungible attribute accessor.
     */
    public function nonFungible(): Attribute
    {
        return new Attribute(
            get: fn () => $this->isNonFungible()
        );
    }

    /**
     * Checks if the token is non-fungible.
     */
    protected function isNonFungible(): bool
    {
        if ($this->is_currency) {
            // If the token is a currency it is fungible.
            return false;
        }

        if ($this->collection->max_token_supply === '1') {
            // If the collection has a rule of maxTokenSupply of 1 means all tokens are NFT
            return true;
        }

        if ($this->collection->force_single_mint && $this->supply === '1') {
            // If the collection has a rule of forceSingleMint and there is only one unit of the token means it is a NFT
            return true;
        }

        if ($this->cap === TokenMintCapType::SUPPLY->name) {
            // If token has a cap of Supply 1, it is non-fungible.
            // If the cap Supply is more than 1, it is fungible.
            return $this->cap_supply === '1';
        }

        if ($this->cap === TokenMintCapType::SINGLE_MINT->name) {
            // If the token is set as SingleMint and only one was minted it is non-fungible
            // If more than one was minted it is fungible.
            return $this->supply === '1';
        }

        // All other cases we will consider the token is fungible.
        return false;
    }

    /**
     * The metadata attribute accessor.
     */
    protected function fetchMetadata(): Attribute
    {
        return new Attribute(
            get: fn () => $this->attributes['fetch_metadata'] ?? false,
            set: function ($value) {
                if ($value === true) {
                    $this->attributes['metadata'] = MetadataService::fetch($this->getRelation('attributes')->first());
                }
                $this->attributes['fetch_metadata'] = $value;
            }
        );
    }

    /**
     * The metadata attribute accessor.
     */
    protected function metadata(): Attribute
    {
        return new Attribute(
            get: function () {
                $tokenUriAttribute = $this->fetchUriAttribute($this);
                $fetchedMetadata = $this->attributes['metadata'] ?? MetadataService::fetch($tokenUriAttribute);

                if (!$fetchedMetadata) {
                    $collectionUriAttribute = $this->fetchUriAttribute($this->collection);
                    if ($collectionUriAttribute && Str::contains($collectionUriAttribute->value, '{id}')) {
                        $collectionUriAttribute->value = Str::replace('{id}', "{$this->collection->collection_chain_id}-{$this->token_chain_id}", $collectionUriAttribute->value);
                    }

                    $fetchedMetadata = MetadataService::fetch($collectionUriAttribute);
                }

                return $fetchedMetadata;
            },
        );
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): TokenFactory
    {
        return TokenFactory::new();
    }

    protected function pivotIdentifier(): Attribute
    {
        if (!$collection = $this->collection) {
            throw new PlatformException(__('enjin-platform::error.no_collection', ['tokenId' => $this->token_chain_id]));
        }

        return Attribute::make(
            get: fn () => "{$collection->collection_chain_id}:{$this->token_chain_id}",
        );
    }

    private function fetchUriAttribute($model)
    {
        return $model->load('attributes')->getRelation('attributes')
            ->filter(fn ($attribute) => $attribute->key == 'uri')
            ->first();
    }
}
