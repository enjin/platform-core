<?php

namespace Enjin\Platform\Database\Factories\Unwritable;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Models\Indexer\Attribute;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Indexer\Token;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attribute>
 */
class AttributeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Attribute::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            // TODO: Right now we are always creating token Attributes
            'token_id' => $token = Token::factory()->create(),
            'collection_id' => $token->collection_id,
            'key' => $key = fake()->unique()->word(),

            'id' => $token->id . '-' . HexConverter::stringToHexPrefixed($key),
            'value' => fake()->text(),
            'deposit' => fake()->randomNumber(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
