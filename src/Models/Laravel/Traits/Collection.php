<?php

namespace Enjin\Platform\Models\Laravel\Traits;

use Enjin\Platform\Models\Laravel\Attribute;
use Enjin\Platform\Models\Laravel\CollectionAccount;
use Enjin\Platform\Models\Laravel\CollectionRoyaltyCurrency;
use Enjin\Platform\Models\Laravel\Token;
use Enjin\Platform\Models\Laravel\Wallet;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait Collection
{
    /**
     * The wallet owner relationship.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'owner_wallet_id');
    }

    /**
     * The creation depositor relationship.
     */
    public function creationDepositor(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'creation_depositor');
    }

    /**
     * The royalty beneficiary relationship.
     */
    public function royaltyBeneficiary(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'royalty_wallet_id');
    }

    /**
     * The royalty currencies relationship.
     */
    public function royaltyCurrencies(): HasMany
    {
        return $this->hasMany(CollectionRoyaltyCurrency::class);
    }

    /**
     * The tokens relationship.
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class);
    }

    /**
     * The collection account relationsip.
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(CollectionAccount::class);
    }

    /**
     * The attributes relationship.
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class, 'collection_id')->whereNull('token_id');
    }
}
