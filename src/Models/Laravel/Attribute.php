<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\Platform\Database\Factories\AttributeFactory;
use Enjin\Platform\Models\BaseModel;
use Enjin\Platform\Models\Laravel\Traits\Attribute as AttributeMethods;
use Enjin\Platform\Models\Laravel\Traits\EagerLoadSelectFields;
use Enjin\Platform\Support\Hex;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeCasts;
use Illuminate\Support\Str;

class Attribute extends BaseModel
{
    use AttributeMethods;
    use EagerLoadSelectFields;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    public $fillable = [
        'collection_id',
        'token_id',
        'key',
        'value',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [];

    /**
     * The attribute key as String.
     */
    public function keyString(): AttributeCasts
    {
        return new AttributeCasts(
            get: fn () => Hex::safeConvertToString($this->key)
        );
    }

    /**
     * The attribute value as String.
     */
    public function valueString(): AttributeCasts
    {
        return new AttributeCasts(
            get: function () {
                $key = Hex::safeConvertToString($this->key);
                $value = Hex::safeConvertToString($this->value);

                if ($key == 'uri' && str_contains($value, '{id}') && $this->token_id) {
                    return Str::replace('{id}', "{$this->token->collection->collection_chain_id}-{$this->token->token_chain_id}", $value);
                }

                return $value;
            }
        );
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): AttributeFactory
    {
        return AttributeFactory::new();
    }
}
