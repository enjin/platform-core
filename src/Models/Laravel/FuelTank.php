<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\Platform\Database\Factories\FuelTankFactory;
use Enjin\Platform\Models\BaseModel;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FuelTank extends BaseModel
{
    use HasFactory;
    use Traits\EagerLoadSelectFields;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    public $guarded = [];

    /**
     * The fillable fields.
     *
     * @var array
     */
    protected $fillable = [
        'public_key',
        'owner_wallet_id',
        'name',
        'coverage_policy',
        'reserves_account_creation_deposit',
        'is_frozen',
        'account_count',
    ];

    /**
     * The hidden fields.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['address'];

    /**
     * The wallet's relationship.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'owner_wallet_id');
    }

    /**
     * The account rules relationship.
     */
    public function accountRules(): HasMany
    {
        return $this->hasMany(AccountRule::class);
    }

    /**
     * The dispatch rules relationship.
     */
    public function dispatchRules(): HasMany
    {
        return $this->hasMany(DispatchRule::class);
    }

    /**
     * The account's relationship.
     */
    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(Wallet::class, 'fuel_tank_accounts');
    }

    /**
     * The address attribute accessor.
     */
    public function address(): Attribute
    {
        return new Attribute(
            get: fn () => is_null($this->public_key) ? null : SS58Address::encode($this->public_key)
        );
    }

    #[\Override]
    protected function pivotIdentifier(): Attribute
    {
        return new Attribute(
            get: fn () => $this->public_key
        );
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<static>
     */
    protected static function newFactory()
    {
        return FuelTankFactory::new();
    }
}
