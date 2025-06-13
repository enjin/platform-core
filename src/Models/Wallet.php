<?php

namespace Enjin\Platform\Models;

use Enjin\Platform\Models\Traits\EagerLoadSelectFields;
use Enjin\Platform\Models\Traits\Unwritable;
use Enjin\Platform\Observers\WalletObserver;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Wallet extends BaseModel
{
    use EagerLoadSelectFields;
    use WalletMethods;
    use Unwritable;

    public $incrementing = false;
    public $timestamps = false;

    protected $connection = 'indexer';
    protected $table = 'account';
    protected $keyType = 'string';

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


//    /**
//     * The attributes that are mass assignable.
//     *
//     * @var array<string>
//     */
//    public $fillable = [
//        'public_key',
//        'external_id',
//        'managed',
//        'verification_id',
//        'network',
//    ];

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [
        'managed' => false,
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
     * Bootstrap the model and its traits.
     *
     * @return void
     */
    #[\Override]
    protected static function boot()
    {
        parent::boot();

        self::observe(new WalletObserver());
    }

    #[\Override]
    protected function pivotIdentifier(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->id,
        );
    }

    /**
     * The collections relationship.
     */
    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class, 'owner_id');
    }

    /**
     * The owned collections relationship.
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
