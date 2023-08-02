<?php

namespace Enjin\Platform\Database\Factories;

use Enjin\Platform\Enums\Substrate\TokenMintCapType;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Token;
use Illuminate\Database\Eloquent\Factories\Factory;

class TokenFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var Token
     */
    protected $model = Token::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'collection_id' => Collection::factory(),
            'token_chain_id' => (string) fake()->unique()->numberBetween(),
            'supply' => (string) $supply = fake()->numberBetween(1),
            'cap' => TokenMintCapType::INFINITE->name,
            'cap_supply' => null,
            'is_frozen' => false,
            'unit_price' => (string) fake()->numberBetween(1 / $supply * 10 ** 17),
            'minimum_balance' => '1',
            'attribute_count' => '0',
        ];
    }
}
