<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\Platform\Database\Factories\FuelTankAccountFactory;
use Enjin\Platform\Models\BaseModel;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelTankAccount extends BaseModel
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
        'fuel_tank_id',
        'wallet_id',
        'tank_deposit',
        'user_deposit',
        'total_received',
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
     * The fuel tank's relationship.
     */
    public function fuelTank(): BelongsTo
    {
        return $this->belongsTo(FuelTank::class);
    }

    /**
     * The wallet's relationship.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Local scope for filtering by wallet public key.
     */
    public function scopeByWalletAccount(Builder $query, array $publicKeys): Builder
    {
        return $query->whereHas(
            'wallet',
            fn ($query) => $query->whereIn('public_key', collect($publicKeys)->map(fn ($key) => SS58Address::getPublicKey($key)))
        );
    }

    /**
     * Local scope for filtering by fuel tank public key.
     */
    public function scopeByFuelTankAccount(Builder $query, string $publicKey): Builder
    {
        return $query->whereHas(
            'fuelTank',
            fn ($query) => $query->where('public_key', SS58Address::getPublicKey($publicKey))
        );
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<static>
     */
    protected static function newFactory()
    {
        return FuelTankAccountFactory::new();
    }
}
