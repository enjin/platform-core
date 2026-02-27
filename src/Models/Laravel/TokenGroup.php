<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Database\Factories\TokenGroupFactory;
use Enjin\Platform\Models\BaseModel;
use Enjin\Platform\Models\Laravel\Traits\EagerLoadSelectFields;
use Enjin\Platform\Models\Laravel\Traits\TokenGroup as TokenGroupMethods;
use Facades\Enjin\Platform\Services\Database\MetadataService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TokenGroup extends BaseModel
{
    use EagerLoadSelectFields;
    use HasFactory;
    use TokenGroupMethods;

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
        'token_group_chain_id',
        'created_at',
        'updated_at',
    ];

    /**
     * The fetch_metadata attribute accessor.
     */
    protected function fetchMetadata(): Attribute
    {
        return new Attribute(
            get: fn () => $this->attributes['fetch_metadata'] ?? false,
            set: function ($value): void {
                if ($value === true) {
                    $this->attributes['metadata'] = MetadataService::getCache($this->getRelation('attributes')->first());
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
            get: fn () => $this->attributes['metadata']
                ?? MetadataService::getCache($this->fetchUriAttribute($this)?->value_string ?? ''),
        );
    }

    protected static function newFactory(): TokenGroupFactory
    {
        return TokenGroupFactory::new();
    }

    private function fetchUriAttribute(self $model): ?\Enjin\Platform\Models\Laravel\Attribute
    {
        if (!$model->relationLoaded('attributes')) {
            $model->load('attributes');
        }

        return $model->getRelation('attributes')
            ->filter(fn ($attribute) => $attribute->key == 'uri' || $attribute->key == HexConverter::stringToHexPrefixed('uri'))
            ->first();
    }
}
