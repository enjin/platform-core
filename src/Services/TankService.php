<?php

namespace Enjin\Platform\Services;

use Enjin\Platform\Models\Indexer\FuelTank;
use Illuminate\Database\Eloquent\Model;

class TankService
{
    /**
     * Get the collection by column and value.
     */
    public function get(string $index, string $column = 'public_key'): Model
    {
        return FuelTank::where($column, '=', $index)->firstOrFail();
    }
}
