<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\Platform\Database\Factories\TokenAccountNamedReserveFactory;
use Enjin\Platform\Models\BaseModel;
use Enjin\Platform\Models\Laravel\Traits\EagerLoadSelectFields;
use Enjin\Platform\Models\Laravel\Traits\TokenAccountNamedReserve as TokenAccountNamedReserveMethods;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TokenAccountNamedReserve extends BaseModel
{
    use HasFactory;
    use TokenAccountNamedReserveMethods;
    use EagerLoadSelectFields;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    public $guarded = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    public $fillable = [
        'token_account_id',
        'pallet',
        'amount',
        'created_at',
        'updated_at',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): TokenAccountNamedReserveFactory
    {
        return TokenAccountNamedReserveFactory::new();
    }
}
