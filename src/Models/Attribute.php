<?php

namespace Enjin\Platform\Models;

use Enjin\Platform\Database\Factories\Unwritable\AttributeFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\Factory;

class Attribute extends UnwritableModel
{
    protected $table = 'attribute';

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

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(Token::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory|AttributeFactory
    {
        return AttributeFactory::new();
    }
}
