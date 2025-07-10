<?php

namespace Enjin\Platform\Models\Indexer;

use Enjin\Platform\Casts\AsBalance;
use Enjin\Platform\Database\Factories\Unwritable\AccountFactory;
use Enjin\Platform\Models\Transaction;
use Enjin\Platform\Models\Verification;
use Enjin\Platform\Observers\WalletObserver;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Override;

class Account extends UnwritableModel
{
    protected $table = 'account';

    protected $visible = [
        'id',
        'address',
        'nonce',
        'balance',
        'last_update_block',
        'username',
        'verified_at',
        'verified',
        'image',
    ];

    /**
     * The tokens attribute accessor.
     */
    public function tokens(): Attribute
    {
        return new Attribute(
            get: fn () => $this->tokenAccounts->pluck('token')
        );
    }

    /**
     * The collection relationship.
     */
    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class, 'owner_id');
    }

    /**
     * The owned collections' relationship.
     */
    public function ownedCollections()
    {
        return $this->hasMany(Collection::class, 'owner_id');
    }

    /**
     * The collection accounts relationship.
     */
    public function collectionAccounts(): HasMany
    {
        return $this->hasMany(CollectionAccount::class, 'account_id');
    }

    /**
     * The token accounts relationship.
     */
    public function tokenAccounts(): HasMany
    {
        return $this->hasMany(TokenAccount::class, 'account_id');
    }

    /**
     * The transaction relationship.
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

    protected function casts(): array
    {
        return [
            'balance' => AsBalance::class,
        ];
    }

    /**
     * Bootstrap the model and its traits.
     */
    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        self::observe(new WalletObserver());
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory|AccountFactory
    {
        return AccountFactory::new();
    }
}
