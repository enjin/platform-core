<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\Platform\Database\Factories\TokenAccountApprovalFactory;
use Enjin\Platform\Models\BaseModel;
use Enjin\Platform\Models\Laravel\Traits\EagerLoadSelectFields;
use Enjin\Platform\Models\Laravel\Traits\TokenAccountApproval as TokenAccountApprovalMethods;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TokenAccountApproval extends BaseModel
{
    use HasFactory;
    use TokenAccountApprovalMethods;
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
        'wallet_id',
        'amount',
        'expiration',
        'created_at',
        'updated_at',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): TokenAccountApprovalFactory
    {
        return TokenAccountApprovalFactory::new();
    }
}
