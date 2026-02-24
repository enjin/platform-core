<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\Platform\Models\BaseModel;
use Enjin\Platform\Models\Laravel\Traits\EagerLoadSelectFields;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenGroupToken extends BaseModel
{
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
        'token_group_id',
        'token_id',
        'position',
        'created_at',
        'updated_at',
    ];

    /**
     * The token group relationship.
     */
    public function tokenGroup(): BelongsTo
    {
        return $this->belongsTo(TokenGroup::class);
    }

    /**
     * The token relationship.
     */
    public function token(): BelongsTo
    {
        return $this->belongsTo(Token::class);
    }
}
