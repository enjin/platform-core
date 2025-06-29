<?php

namespace Enjin\Platform\Models;

use Enjin\Platform\Database\Factories\Unwritable\TokenAccountFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\Factory;

class TokenAccount extends UnwritableModel
{
    protected $table = 'token_account';

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

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(Token::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
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

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory|TokenAccountFactory
    {
        return TokenAccountFactory::new();
    }
}
