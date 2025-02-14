<?php

namespace Enjin\Platform\Services;

use Enjin\Platform\Models\FuelTank;
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

    /**
     * Create a new collection.
     */
    public function store(array $data): Model
    {
        return FuelTank::create($data);
    }

    /**
     * Insert a new collection.
     */
    public function insert(array $data): bool
    {
        return FuelTank::insert($data);
    }
}
