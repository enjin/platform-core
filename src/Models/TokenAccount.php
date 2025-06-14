<?php

namespace Enjin\Platform\Models;

use Enjin\Platform\Models\Traits\EagerLoadSelectFields;
use Enjin\Platform\Models\Traits\Unwritable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TokenAccount extends Model
{
    use EagerLoadSelectFields;
    use Unwritable;

    public $incrementing = false;

    protected $connection = 'indexer';
    protected $table = 'token_account';
    protected $keyType = 'string';

    protected $visible = [
        'id',
        'total_balance',
        'balance',
        'reserved_balance',
        'locked_balance',
        'named_reserves',
        'locks',
        'approvals',
        'is_frozen',
        'created_at',
        'updated_at',
        'account_id',
        'collection_id',
        'token_id',
    ];

    //  public $fillable = [
    //        'wallet_id',
    //        'collection_id',
    //        'token_id',
    //        'balance',
    //        'reserved_balance',
    //        'is_frozen',
    //        'created_at',
    //        'updated_at',
    //    ];
    //
    //    /**
    //     * The model's attributes.
    //     *
    //     * @var array
    //     */
    //    protected $attributes = [
    //        'balance' => '1',
    //        'reserved_balance' => '0',
    //        'is_frozen' => false,
    //    ];

    /**
     * The collection relationship.
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * The token relationship.
     */
    public function token(): BelongsTo
    {
        return $this->belongsTo(Token::class);
    }

    /**
     * The wallet relationship.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * The token account approvals relationship.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(TokenAccountApproval::class);
    }

    /**
     * The token account named reserves relationship.
     */
    public function namedReserves(): HasMany
    {
        return $this->hasMany(TokenAccountNamedReserve::class);
    }

    protected function casts(): array
    {
        return [
            'named_reserves' => 'array',
            'locks' => 'array',
            'approvals' => 'array',
            'is_frozen' => 'boolean',
        ];
    }
}
