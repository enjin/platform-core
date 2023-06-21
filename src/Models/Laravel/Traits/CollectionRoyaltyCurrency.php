<?php

namespace Enjin\Platform\Models\Laravel\Traits;

use Enjin\Platform\Models\Laravel\Collection;
use Enjin\Platform\Models\Laravel\Token;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait CollectionRoyaltyCurrency
{
    /**
     * The collection relationship.
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * The currency relationship.
     */
    public function currency(): HasOne
    {
        return $this->hasOne(Token::class);
    }
}
