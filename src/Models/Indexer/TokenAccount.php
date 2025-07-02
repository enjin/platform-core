<?php

namespace Enjin\Platform\Models\Indexer;

use Enjin\Platform\Database\Factories\Unwritable\TokenAccountFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenAccount extends UnwritableModel
{
    /**
     * The table name in the indexer database.
     */
    protected $table = 'token_account';

    /**
     * The columns from the table.
     */
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

    /**
     * The collection this token account belongs to.
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * The token this token account belongs to.
     */
    public function token(): BelongsTo
    {
        return $this->belongsTo(Token::class);
    }

    /**
     * The account this token account belongs to.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Account::class);
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
