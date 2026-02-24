<?php

namespace Enjin\Platform\Models\Laravel\Traits;

use Enjin\Platform\Models\Laravel\Collection;
use Enjin\Platform\Models\Laravel\Token;
use Enjin\Platform\Models\Laravel\TokenGroup;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait Attribute
{
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
     * The token group relationship.
     */
    public function tokenGroup(): BelongsTo
    {
        return $this->belongsTo(TokenGroup::class);
    }
}
