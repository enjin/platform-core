<?php

namespace Enjin\Platform\Models\Laravel\Traits;

use Enjin\Platform\Models\Laravel\Attribute;
use Enjin\Platform\Models\Laravel\Collection;
use Enjin\Platform\Models\Laravel\TokenAccount;
use Enjin\Platform\Models\Laravel\Wallet;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait Token
{
    /**
     * The collection relationship.
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * The royalty benificiary relationship.
     */
    public function royaltyBeneficiary(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'royalty_wallet_id');
    }

    /**
     * The token accounts relationship.
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(TokenAccount::class);
    }

    /**
     * The attributes relationship.
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class, 'token_id');
    }
}
