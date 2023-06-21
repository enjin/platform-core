<?php

namespace Enjin\Platform\Models\Laravel\Traits;

use Enjin\Platform\Models\Laravel\Collection;
use Enjin\Platform\Models\Laravel\Token;
use Enjin\Platform\Models\Laravel\TokenAccountApproval;
use Enjin\Platform\Models\Laravel\TokenAccountNamedReserve;
use Enjin\Platform\Models\Laravel\Wallet;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait TokenAccount
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
     * The wallet relationship.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * The token account approvals relationship.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(TokenAccountApproval::class);
    }

    /**
     * The token account named reserves relationship.
     */
    public function namedReserves(): HasMany
    {
        return $this->hasMany(TokenAccountNamedReserve::class);
    }
}
