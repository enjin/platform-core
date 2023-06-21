<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\Platform\Database\Factories\BlockFactory;
use Enjin\Platform\Models\BaseModel;
use Enjin\Platform\Models\Laravel\Traits\EagerLoadSelectFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Block extends BaseModel
{
    use HasFactory;
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
        'number',
        'hash',
        'synced',
        'failed',
        'exception',
        'retried',
        'events',
        'extrinsics',
        'created_at',
        'updated_at',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): BlockFactory
    {
        return BlockFactory::new();
    }
}
