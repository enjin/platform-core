<?php

namespace Enjin\Platform\Database\Factories;

use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\TokenGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;

class TokenGroupFactory extends Factory
{
    protected $model = TokenGroup::class;

    public function __construct($count = null, ?Collection $states = null, ?Collection $has = null, ?Collection $for = null, ?Collection $afterMaking = null, ?Collection $afterCreating = null, $connection = null, ?Collection $recycle = null)
    {
        parent::__construct($count, $states, $has, $for, $afterMaking, $afterCreating, $connection, $recycle);

        $this->model = TokenGroup::resolveClassFqn();
    }

    public function definition(): array
    {
        return [
            'collection_id' => Collection::factory(),
            'token_group_chain_id' => (string) fake()->unique()->numberBetween(1),
        ];
    }
}
