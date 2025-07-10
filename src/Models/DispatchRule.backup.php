<?php

namespace Enjin\Platform\Models;

use Enjin\Platform\Database\Factories\DispatchRuleFactory;
use Enjin\Platform\Models\Indexer\FuelTank;
use Enjin\Platform\Models\Indexer\UnwritableModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchRule extends UnwritableModel
{
    /**
     * The table name.
     */
    protected $table = 'fuel_tank_dispatch_rules';

    /**
     * The fillable fields.
     *
     * @var array
     */
    protected $fillable = [
        'fuel_tank_id',
        'rule_set_id',
        'rule',
        'value',
        'is_frozen',
    ];

    /**
     * The hidden fields.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'value' => 'array',
    ];

    /**
     * The fuel tank's relationship.
     */
    public function fuelTank(): BelongsTo
    {
        return $this->belongsTo(FuelTank::class);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<static>
     */
    protected static function newFactory()
    {
        return DispatchRuleFactory::new();
    }
}
