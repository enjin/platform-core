<?php

namespace Enjin\Platform\Database\Factories;

use Enjin\Platform\Models\CollectionRoyaltyCurrency;
use Enjin\Platform\Models\Indexer\Collection;
use Enjin\Platform\Models\Indexer\Token;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollectionRoyaltyCurrencyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var CollectionRoyaltyCurrency
     */
    protected $model = CollectionRoyaltyCurrency::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'collection_id' => Collection::factory(),
            'currency_collection_chain_id' => Collection::factory()->create()->collection_chain_id,
            'currency_token_chain_id' => Token::factory()->create()->token_chain_id,
        ];
    }
}
