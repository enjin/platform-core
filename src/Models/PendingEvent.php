<?php

namespace Enjin\Platform\Models;

use Enjin\Platform\Database\Factories\PendingEventFactory;
use Enjin\Platform\Models\Traits\EagerLoadSelectFields;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;

class PendingEvent extends BaseModel
{
    use EagerLoadSelectFields;
    use HasFactory;
    use MassPrunable;

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
        'network',
    ];

    /**
     * Get the prunable model query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function prunable()
    {
        if ($days = config('enjin-platform.prune_expired_events')) {
            return static::where('sent', '<=', now()->subDays($days));
        }

        return static::where('id', 0);
    }

    #[\Override]
    protected function pivotIdentifier(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->uuid,
        );
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PendingEventFactory
    {
        return PendingEventFactory::new();
    }
}
