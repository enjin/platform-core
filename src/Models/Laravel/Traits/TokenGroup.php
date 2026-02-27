<?php

namespace Enjin\Platform\Models\Laravel\Traits;

use Enjin\Platform\Models\Laravel\Attribute;
use Enjin\Platform\Models\Laravel\Collection;
use Enjin\Platform\Models\Laravel\TokenGroupToken;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait TokenGroup
{
    /**
     * The collection relationship.
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * The token group tokens relationship.
     */
    public function tokenGroupTokens(): HasMany
    {
        return $this->hasMany(TokenGroupToken::class);
    }

    /**
     * The attributes relationship.
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class, 'token_group_id');
    }
}
