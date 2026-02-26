<?php

namespace Enjin\Platform\Database\Factories;

use Enjin\Platform\Models\Token;
use Enjin\Platform\Models\TokenGroup;
use Enjin\Platform\Models\TokenGroupToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;

class TokenGroupTokenFactory extends Factory
{
    protected $model = TokenGroupToken::class;

    public function __construct($count = null, ?Collection $states = null, ?Collection $has = null, ?Collection $for = null, ?Collection $afterMaking = null, ?Collection $afterCreating = null, $connection = null, ?Collection $recycle = null)
    {
        parent::__construct($count, $states, $has, $for, $afterMaking, $afterCreating, $connection, $recycle);

        $this->model = TokenGroupToken::resolveClassFqn();
    }

    public function definition(): array
    {
        return [
            'token_group_id' => TokenGroup::factory(),
            'token_id' => Token::factory(),
            'position' => fake()->numberBetween(0, 100),
        ];
    }
}
