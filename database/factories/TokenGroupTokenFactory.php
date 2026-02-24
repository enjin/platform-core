<?php

namespace Enjin\Platform\Database\Factories;

use Enjin\Platform\Models\Token;
use Enjin\Platform\Models\TokenGroup;
use Enjin\Platform\Models\TokenGroupToken;
use Illuminate\Database\Eloquent\Factories\Factory;

class TokenGroupTokenFactory extends Factory
{
    protected $model = TokenGroupToken::class;

    public function __construct($count = null, ?\Illuminate\Support\Collection $states = null, ?\Illuminate\Support\Collection $has = null, ?\Illuminate\Support\Collection $for = null, ?\Illuminate\Support\Collection $afterMaking = null, ?\Illuminate\Support\Collection $afterCreating = null, $connection = null, ?\Illuminate\Support\Collection $recycle = null)
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
