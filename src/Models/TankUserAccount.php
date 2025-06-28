<?php

namespace Enjin\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TankUserAccount extends UnwritableModel
{
    protected $table = 'fuel_tank_user_accounts';
    protected $visible = [
        'id',
        'tank_deposit',
        'user_deposit',
        'tank_id',
        'account_id',
    ];

    //    protected $fillable = [
    //        'fuel_tank_id',
    //        'wallet_id',
    //        'tank_deposit',
    //        'user_deposit',
    //        'total_received',
    //    ];

    public function fuelTank(): BelongsTo
    {
        return $this->belongsTo(FuelTank::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    //    public function scopeByWalletAccount(Builder $query, array $publicKeys): Builder
    //    {
    //        return $query->whereHas(
    //            'wallet',
    //            fn ($query) => $query->whereIn('public_key', collect($publicKeys)->map(fn ($key) => SS58Address::getPublicKey($key)))
    //        );
    //    }
    //
    //    /**
    //     * Local scope for filtering by fuel tank public key.
    //     */
    //    public function scopeByFuelTankAccount(Builder $query, string $publicKey): Builder
    //    {
    //        return $query->whereHas(
    //            'fuelTank',
    //            fn ($query) => $query->where('public_key', SS58Address::getPublicKey($publicKey))
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
    //        return FuelTankAccountFactory::new();
    //    }
}
