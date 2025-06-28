<?php

namespace Enjin\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
