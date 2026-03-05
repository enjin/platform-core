<?php

namespace Enjin\Platform\Models\Laravel;

use Enjin\Platform\Database\Factories\BlockFactory;
use Enjin\Platform\Models\BaseModel;
use Enjin\Platform\Models\Laravel\Traits\EagerLoadSelectFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;

class Block extends BaseModel
{
    use EagerLoadSelectFields;
    use HasFactory;
    use MassPrunable;

    /**
     * The attributes that aren't mass-assignable.
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
        'timestamp',
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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'timestamp' => 'datetime',
        'synced' => 'boolean',
        'failed' => 'boolean',
        'retried' => 'boolean',
    ];

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        if (!empty($days = config('enjin-platform.prune_blocks'))) {
            $cutoff = now()->subDays($days);

            return static::where(function (Builder $query) use ($cutoff): void {
                $query->where(function (Builder $query) use ($cutoff): void {
                    $query->whereNotNull('timestamp')
                        ->where('timestamp', '<=', $cutoff);
                })->orWhere(function (Builder $query) use ($cutoff): void {
                    $query->whereNull('timestamp')
                        ->where('created_at', '<=', $cutoff);
                });
            });
        }

        return static::where('id', 0);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): BlockFactory
    {
        return BlockFactory::new();
    }
}
