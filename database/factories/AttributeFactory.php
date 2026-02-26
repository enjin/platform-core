<?php

namespace Enjin\Platform\Database\Factories;

use Enjin\BlockchainTools\HexConverter;
use Enjin\Platform\Models\Attribute;
use Enjin\Platform\Models\Collection;
use Enjin\Platform\Models\Token;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;

class AttributeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var Attribute
     */
    protected $model = Attribute::class;

    public function __construct($count = null, ?Collection $states = null, ?Collection $has = null, ?Collection $for = null, ?Collection $afterMaking = null, ?Collection $afterCreating = null, $connection = null, ?Collection $recycle = null)
    {
        parent::__construct($count, $states, $has, $for, $afterMaking, $afterCreating, $connection, $recycle);

        $this->model = Attribute::resolveClassFqn();
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'collection_id' => Collection::factory(),
            'token_id' => Token::factory(),
            'key' => HexConverter::stringToHexPrefixed(fake()->unique()->word()),
            'value' => HexConverter::stringToHexPrefixed(fake()->text()),
        ];
    }
}
