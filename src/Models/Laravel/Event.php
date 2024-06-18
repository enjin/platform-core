<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\Platform\Database\Factories\EventFactory;
use Enjin\Platform\Models\BaseModel;
use Enjin\Platform\Models\Laravel\Traits\EagerLoadSelectFields;
use Enjin\Platform\Models\Laravel\Traits\Event as EventMethods;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends BaseModel
{
    use EagerLoadSelectFields;
    use EventMethods;
    use HasFactory;

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
        'transaction_id',
        'phase',
        'look_up',
        'module_id',
        'event_id',
        'params',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): EventFactory
    {
        return EventFactory::new();
    }
}
