<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\Platform\Models\BaseModel;
use Enjin\Platform\Models\Laravel\Traits\Syncable as IndexMethods;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Syncable extends BaseModel
{
    use HasFactory;
    use IndexMethods;
    use SoftDeletes;

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
        'syncable_id',
        'syncable_type',
    ];

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [];

    protected function pivotIdentifier(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->toJson(),
        );
    }
}
