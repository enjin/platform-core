<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\Platform\Database\Factories\TokenGroupTokenFactory;
use Enjin\Platform\Models\BaseModel;
use Enjin\Platform\Models\Laravel\Traits\EagerLoadSelectFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenGroupToken extends BaseModel
{
    use EagerLoadSelectFields;
    use HasFactory;

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
     * The token relationship.
     */
    public function token(): BelongsTo
    {
        return $this->belongsTo(Token::class);
    }

    /**
     * The token group relationship.
     */
    public function tokenGroup(): BelongsTo
    {
        return $this->belongsTo(TokenGroup::class);
    }

    protected static function newFactory(): TokenGroupTokenFactory
    {
        return TokenGroupTokenFactory::new();
    }
}
