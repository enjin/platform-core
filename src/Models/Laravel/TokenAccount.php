<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\Platform\Database\Factories\TokenAccountFactory;
use Enjin\Platform\Models\BaseModel;
use Enjin\Platform\Models\Laravel\Traits\EagerLoadSelectFields;
use Enjin\Platform\Models\Laravel\Traits\TokenAccount as TokenAccountMethods;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Staudenmeir\EloquentEagerLimit\HasEagerLimit;

class TokenAccount extends BaseModel
{
    use HasFactory;
    use TokenAccountMethods;
    use EagerLoadSelectFields;
    use HasEagerLimit;

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
        'wallet_id',
        'collection_id',
        'token_id',
        'balance',
        'reserved_balance',
        'is_frozen',
        'created_at',
        'updated_at',
    ];

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [
        'balance' => '1',
        'reserved_balance' => '0',
        'is_frozen' => false,
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): TokenAccountFactory
    {
        return TokenAccountFactory::new();
    }
}
