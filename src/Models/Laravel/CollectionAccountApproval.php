<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\Platform\Database\Factories\CollectionAccountApprovalFactory;
use Enjin\Platform\Models\BaseModel;
use Enjin\Platform\Models\Laravel\Traits\CollectionAccountApproval as CollectionAccountApprovalMethods;
use Enjin\Platform\Models\Laravel\Traits\EagerLoadSelectFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionAccountApproval extends BaseModel
{
    use HasFactory;
    use CollectionAccountApprovalMethods;
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
        'collection_account_id',
        'wallet_id',
        'expiration',
        'created_at',
        'updated_at',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CollectionAccountApprovalFactory
    {
        return CollectionAccountApprovalFactory::new();
    }
}
