<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\Platform\Database\Factories\AttributeFactory;
use Enjin\Platform\Models\BaseModel;
use Enjin\Platform\Models\Laravel\Traits\Attribute as AttributeMethods;
use Enjin\Platform\Models\Laravel\Traits\EagerLoadSelectFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attribute extends BaseModel
{
    use AttributeMethods;
    use EagerLoadSelectFields;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    public $fillable = [
        'collection_id',
        'token_id',
        'key',
        'value',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): AttributeFactory
    {
        return AttributeFactory::new();
    }
}
