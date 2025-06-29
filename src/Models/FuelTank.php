<?php

namespace Enjin\Platform\Models;

use Enjin\Platform\Database\Factories\Unwritable\FuelTankFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\Factory;

class FuelTank extends UnwritableModel
{
    protected $table = 'fuel_tank';

    protected $visible = [
        'id',
        'name',
        'provides_deposit',
        'is_frozen',
        'account_count',
        'coverage_policy',
        'user_account_management',
        'tank_account_id',
        'owner_id',
    ];

    protected $casts = [
        'user_account_management' => 'array',
    ];

    //    protected $fillable = [
    //        'public_key',
    //        'owner_wallet_id',
    //        'name',
    //        'coverage_policy',
    //        'reserves_account_creation_deposit',
    //        'is_frozen',
    //        'account_count',
    //    ];
    //
    //    /**
    //     * The accessors to append to the model's array form.
    //     *
    //     * @var array
    //     */
    //    protected $appends = ['address'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'owner_id');
    }

    public function accountRules(): HasMany
    {
        return $this->hasMany(AccountRule::class, 'tank_id');
    }

    public function dispatchRules(): HasMany
    {
        return $this->hasMany(DispatchRule::class);
    }

    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(Wallet::class, 'fuel_tank_accounts');
    }

    //    public function address(): Attribute
    //    {
    //        return new Attribute(
    //            get: fn () => is_null($this->public_key) ? null : SS58Address::encode($this->public_key)
    //        );
    //    }
    //
    //    #[\Override]
    //    protected function pivotIdentifier(): Attribute
    //    {
    //        return new Attribute(
    //            get: fn () => $this->public_key
    //        );
    //    }
    //
    //    /**
    //     * Create a new factory instance for the model.
    //     *
    //     * @return \Illuminate\Database\Eloquent\Factories\Factory<static>
    //     */
    //    protected static function newFactory()
    //    {
    //        return FuelTankFactory::new();
    //    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory|FuelTankFactory
    {
        return FuelTankFactory::new();
    }
}
