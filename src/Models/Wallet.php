<?php

namespace Enjin\Platform\Models;

use Enjin\Platform\Database\Factories\Unwritable\WalletFactory;
use Enjin\Platform\Observers\WalletObserver;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Override;

class Wallet extends UnwritableModel
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

    //    public $fillable = [
    //        'public_key',
    //        'external_id',
    //        'managed',
    //        'verification_id',
    //        'network',
    //    ];

    //    /**
    //     * The model's attributes.
    //     *
    //     * @var array
    //     */
    //    protected $attributes = [
    //        'managed' => false,
    //    ];

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
            'balance' => 'array',
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

    //    #[\Override]
    //    protected function pivotIdentifier(): Attribute
    //    {
    //        return Attribute::make(
    //            get: fn () => $this->id,
    //        );
    //    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory|WalletFactory
    {
        return WalletFactory::new();
    }
}
