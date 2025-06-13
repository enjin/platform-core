<?php

namespace Enjin\Platform\Models;

use Enjin\Platform\Models\Traits\EagerLoadSelectFields;
use Enjin\Platform\Models\Traits\Unwritable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attribute extends BaseModel
{
    use EagerLoadSelectFields;
    use Unwritable;

    public $incrementing = false;

    protected $connection = 'indexer';
    protected $table = 'attribute';
    protected $keyType = 'string';


    protected $visible = [
        'id',
        'key',
        'value',
        'deposit',
        'created_at',
        'updated_at',
        'collection_id',
        'token_id',
    ];

    /**
     * The collection relationship.
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * The token relationship.
     */
    public function token(): BelongsTo
    {
        return $this->belongsTo(Token::class);
    }

    /**
     * The attribute value as String.
     */
//    protected function valueString(): AttributeCasts
//    {
//        return AttributeCasts::make(
//            get: function (?string $value, ?array $attributes) {
//                $key = Hex::safeConvertToString($attributes['key']);
//                $value = Hex::safeConvertToString($attributes['value']);
//
//                if ($key == 'uri' && str_contains($value, '{id}')) {
//                    if (!$this->relationLoaded('collection')) {
//                        $this->load('collection:id,collection_chain_id');
//                    }
//
//                    if (!$this->relationLoaded('token')) {
//                        $this->load('token:id,token_chain_id');
//                    }
//
//                    return Str::replace('{id}', "{$this->collection->collection_chain_id}-{$this->token?->token_chain_id}", $value);
//                }
//
//                return $value;
//            }
//        );
//    }
}
