<?php

namespace Enjin\Platform\Models;

use Enjin\Platform\Database\Factories\Unwritable\TransactionFactory;
use Enjin\Platform\Enums\Global\TransactionState;
use Enjin\Platform\Models\Traits\SelectFields;
use Enjin\Platform\Support\Address;
use Enjin\Platform\Support\SS58Address;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

class Transaction extends Model
{
    use SelectFields;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    public $fillable = [
        'id', // Equals to an idempotency key
        'idempotency_key',

        'extrinsic_id', // Equals to blockNumber-index
        'extrinsic_hash', // Set by wallet daemon

        'signer_id', // Equals to wallet_public_key

        'method',
        'state',
        'result',
        'events',
        'encoded_data',
        'fee',
        'deposit',
        'network',

        'signed_at_block',

        'created_at',
        'updated_at',
    ];

//    /**
//     * The accessors to append to the model's array form.
//     */
//    protected $appends = ['wallet_address'];
//
//    /**
//     * Create a new model instance.
//     */
//    public function __construct(array $attributes = [])
//    {
//        $attributes['state'] ??= TransactionState::PENDING->name;
//
//        parent::__construct($attributes);
//    }
//
//    #[Override]
//    public static function boot(): void
//    {
//        parent::boot();
//
//        static::creating(
//            fn ($model) => $model->managed = (int) (empty($model->wallet_public_key) || Account::isManaged($model->wallet_public_key)),
//        );
//
//        static::updating(
//            fn ($model) => $model->managed = (int) (empty($model->wallet_public_key) || Account::isManaged($model->wallet_public_key)),
//        );
//    }
//
//    /**
//     * The wallet relationship.
//     */
//    public function wallet(): BelongsTo
//    {
//        return $this->belongsTo(Wallet::class, 'wallet_public_key', 'public_key');
//    }
//
//    /**
//     * The events' relationship.
//     */
//    public function events(): HasMany
//    {
//        return $this->hasMany(Event::class);
//    }
//
//    /**
//     * The wallet address attribute accessor.
//     */
//    protected function walletAddress(): Attribute
//    {
//        return new Attribute(
//            get: fn () => SS58Address::encode($this->wallet_public_key)
//        );
//    }
//
    //    #[\Override]
    //    protected function pivotIdentifier(): Attribute
    //    {
    //        return Attribute::make(
    //            get: fn () => $this->idempotency_key,
    //        );
    //    }
    //
    //    /**
    //     * Create a new factory instance for the model.
    //     */
    //    protected static function newFactory(): TransactionFactory
    //    {
    //        return TransactionFactory::new();
    //    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory|TransactionFactory
    {
        return TransactionFactory::new();
    }
}
