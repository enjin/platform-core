<?php

namespace Enjin\Platform\Database\Factories;

use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\TokenGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class TokenGroupFactory extends Factory
{
    protected $model = TokenGroup::class;

    public function __construct($count = null, ?\Illuminate\Support\Collection $states = null, ?\Illuminate\Support\Collection $has = null, ?\Illuminate\Support\Collection $for = null, ?\Illuminate\Support\Collection $afterMaking = null, ?\Illuminate\Support\Collection $afterCreating = null, $connection = null, ?\Illuminate\Support\Collection $recycle = null)
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
