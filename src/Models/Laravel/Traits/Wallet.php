<?php

namespace Enjin\Platform\Models\Laravel\Traits;

use Enjin\Platform\Models\Laravel\Collection;
use Enjin\Platform\Models\Laravel\CollectionAccount;
use Enjin\Platform\Models\Laravel\CollectionAccountApproval;
use Enjin\Platform\Models\Laravel\TokenAccount;
use Enjin\Platform\Models\Laravel\TokenAccountApproval;
use Enjin\Platform\Models\Laravel\Transaction;
use Enjin\Platform\Models\Laravel\Verification;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait Wallet
{
    /**
     * The collections relationship.
     */
    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class, 'owner_wallet_id');
    }

    /**
     * The owned collections relationship.
     */
    public function ownedCollections()
    {
        return $this->hasMany(Collection::class, 'owner_wallet_id');
    }

    /**
     * The collection accounts relationship.
     */
    public function collectionAccounts(): HasMany
    {
        return $this->hasMany(CollectionAccount::class);
    }

    /**
     * The token accounts relationship.
     */
    public function tokenAccounts(): HasMany
    {
        return $this->hasMany(TokenAccount::class);
    }

    /**
     * The transactions relationship.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'wallet_public_key', 'public_key');
    }

    /**
     * The verification relationship.
     */
    public function verification(): HasOne
    {
        return $this->hasOne(Verification::class);
    }

    /**
     * The collection account approvals relationship.
     */
    public function collectionAccountApprovals(): HasMany
    {
        return $this->hasMany(CollectionAccountApproval::class);
    }

    /**
     * The token account approvals relationship.
     */
    public function tokenAccountApprovals(): HasMany
    {
        return $this->hasMany(TokenAccountApproval::class);
    }
}
