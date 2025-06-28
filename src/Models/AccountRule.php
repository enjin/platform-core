<?php

namespace Enjin\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountRule extends UnwritableModel
{
    protected $table = 'fuel_tank_account_rules';

    protected $visible = [
        'id',
        'rule',
        'tank_id',
    ];

    protected $casts = [
        'rule' => 'array',
    ];

    public function fuelTank(): BelongsTo
    {
        return $this->belongsTo(FuelTank::class, 'tank_id');
    }
}
