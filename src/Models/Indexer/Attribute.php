<?php

namespace Enjin\Platform\Models\Indexer;

use Enjin\Platform\Database\Factories\Unwritable\AttributeFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attribute extends UnwritableModel
{
    /**
     * The table name in the indexer database.
     */
    protected $table = 'attribute';

    /**
     * The columns from the table.
     */
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
     * The collection this attribute belongs to.
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * The token this attribute belongs to.
     */
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
