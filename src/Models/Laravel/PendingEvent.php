<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\Platform\Models\BaseModel;
use Enjin\Platform\Models\Laravel\Traits\EagerLoadSelectFields;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PendingEvent extends BaseModel
{
    use EagerLoadSelectFields;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

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
        'uuid',
        'name',
        'sent',
        'channels',
        'data',
    ];

    protected function pivotIdentifier(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->uuid,
        );
    }
}
